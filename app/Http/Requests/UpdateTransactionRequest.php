<?php

namespace App\Http\Requests;

/**
 * Validação da atualização de transação.
 *
 * O PUT representa a transação completa: as mesmas regras da criação se aplicam
 * (mesmo contrato de `amount` em centavos).
 */
class UpdateTransactionRequest extends StoreTransactionRequest {}
