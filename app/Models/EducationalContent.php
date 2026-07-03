<?php

namespace App\Models;

use Database\Factories\EducationalContentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EducationalContent extends Model
{
    /** @use HasFactory<EducationalContentFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'title',
        'theme',
        'body',
    ];
}
