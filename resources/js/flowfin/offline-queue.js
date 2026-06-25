// Fila offline de transações (Task 5.2) — GARANTIA DE ZERO PERDA DE DADOS.
//
// Quando o usuário registra/edita uma transação SEM conexão, a operação é
// gravada em IndexedDB (persiste mesmo se ele fechar o app) e sincronizada
// automaticamente ao reconectar. A UI continua chamando o ponto único de
// escrita (`api.persistTransaction`); a interceptação acontece ali e delega
// para esta fila quando não há rede.
//
// Anti-duplicidade:
//  - Cada operação ganha um `uuid` (idempotency key) gerado no cliente.
//  - O registro só sai da fila APÓS confirmação de sucesso do servidor (2xx).
//    Assim, nunca reenviamos algo já confirmado.
//  - O `uuid` é enviado ao backend (campo `client_uuid`) para reconciliação
//    futura. Hoje o backend ignora campos não validados; a dedup do lado do
//    servidor (caso a resposta de um POST bem-sucedido se perca) depende de
//    suporte no backend — ver Task Log (decisão/limitação reportada).

const DB_NAME = 'flowfin';
const DB_VERSION = 1;
const STORE = 'tx_queue';

let _persist = null;     // função de rede injetada por api.js (evita import circular)
let _flushing = false;

function uuid() {
    if (crypto.randomUUID) return crypto.randomUUID();
    // Fallback simples (ambientes sem randomUUID).
    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, (c) => {
        const r = (Math.random() * 16) | 0;
        const v = c === 'x' ? r : (r & 0x3) | 0x8;
        return v.toString(16);
    });
}

function openDB() {
    return new Promise((resolve, reject) => {
        const req = indexedDB.open(DB_NAME, DB_VERSION);
        req.onupgradeneeded = () => {
            const db = req.result;
            if (!db.objectStoreNames.contains(STORE)) {
                const store = db.createObjectStore(STORE, { keyPath: 'uuid' });
                store.createIndex('createdAt', 'createdAt');
            }
        };
        req.onsuccess = () => resolve(req.result);
        req.onerror = () => reject(req.error);
    });
}

async function withStore(mode, fn) {
    const db = await openDB();
    return new Promise((resolve, reject) => {
        const tx = db.transaction(STORE, mode);
        const store = tx.objectStore(STORE);
        const result = fn(store);
        tx.oncomplete = () => resolve(result.value);
        tx.onerror = () => reject(tx.error);
        tx.onabort = () => reject(tx.error);
        // Para requests de leitura, capturamos o valor no onsuccess.
        if (result.request) {
            result.request.onsuccess = () => { result.value = result.request.result; };
        }
    });
}

function emit(name, detail) {
    window.dispatchEvent(new CustomEvent(name, { detail }));
}

/** Monta a transação "otimista" devolvida à UI quando a operação fica pendente. */
function syntheticTx(record) {
    return {
        ...record.payload,
        id: record.op === 'update' ? record.id : `pending:${record.uuid}`,
        _pending: true,
        _uuid: record.uuid,
        created_at: record.createdAt,
        updated_at: record.createdAt,
    };
}

const offlineQueue = {
    /** Injeta a função de rede usada para sincronizar (definida em api.js). */
    configure(persistFn) {
        _persist = persistFn;
    },

    /**
     * Enfileira uma operação de transação. Resolve com a transação otimista
     * (marcada com `_pending: true`) para a UI exibir imediatamente.
     * @param {{op:'create'|'update', id?:number|null, payload:object}} args
     */
    async enqueue({ op, id = null, payload }) {
        const record = {
            uuid: uuid(),
            op,
            id,
            payload,
            status: 'pending',
            attempts: 0,
            error: null,
            createdAt: new Date().toISOString(),
        };
        await withStore('readwrite', (store) => ({ request: store.put(record) }));
        emit('flowfin:queue-changed', { count: await this.pendingCount() });
        return syntheticTx(record);
    },

    /** Todas as operações pendentes, em ordem de criação. */
    async all() {
        const items = await withStore('readonly', (store) => ({ request: store.getAll() }));
        return (items || []).sort((a, b) => (a.createdAt < b.createdAt ? -1 : 1));
    },

    async pendingCount() {
        const items = await this.all();
        return items.length;
    },

    async _remove(uuidKey) {
        await withStore('readwrite', (store) => ({ request: store.delete(uuidKey) }));
    },

    /**
     * Sincroniza a fila com o servidor (chamado ao reconectar / ao carregar).
     * Reaplica em ordem; remove cada item só após sucesso confirmado.
     */
    async flush() {
        if (_flushing || !_persist) return;
        if (!navigator.onLine) return;

        _flushing = true;
        let synced = 0;
        try {
            const items = await this.all();
            if (!items.length) return;

            emit('flowfin:sync-start', { count: items.length });

            for (const record of items) {
                try {
                    const payload = { ...record.payload, client_uuid: record.uuid };
                    const tx = await _persist(payload, record.op === 'update' ? record.id : null);
                    await this._remove(record.uuid);
                    synced++;
                    // Avisa a UI: item sincronizado + listas podem recarregar (online).
                    emit('flowfin:tx-synced', { uuid: record.uuid, tx });
                    emit('transaction-saved', tx);
                } catch (err) {
                    const status = err && typeof err.status === 'number' ? err.status : null;
                    if (status === 0 || status === null) {
                        // Voltou a cair a rede: para e tenta de novo na próxima reconexão.
                        break;
                    }
                    if (status === 422 || (status >= 400 && status < 500 && status !== 429)) {
                        // Erro permanente (dados inválidos / não autorizado): não adianta repetir.
                        // Remove da fila e sinaliza para o usuário não ficar preso para sempre.
                        await this._remove(record.uuid);
                        emit('flowfin:tx-failed', { uuid: record.uuid, error: err.message, payload: record.payload });
                    } else {
                        // Erro transitório do servidor (5xx/429): mantém na fila e tenta depois.
                        break;
                    }
                }
            }
        } finally {
            _flushing = false;
            const remaining = await this.pendingCount();
            emit('flowfin:queue-changed', { count: remaining });
            if (synced > 0) emit('flowfin:sync-complete', { synced, remaining });
        }
    },

    /** Liga os gatilhos automáticos de sincronização. */
    init() {
        window.addEventListener('online', () => this.flush());
        document.addEventListener('visibilitychange', () => {
            if (document.visibilityState === 'visible') this.flush();
        });
        // Tenta esvaziar pendências assim que o app carrega (se houver rede).
        if (navigator.onLine) this.flush();
    },
};

export { offlineQueue };
