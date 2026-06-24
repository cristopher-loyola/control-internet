<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ApiToken extends Model
{
    protected $fillable = ['usuario_id', 'token', 'expires_at', 'last_used_at'];

    protected $casts = [
        'expires_at'   => 'datetime',
        'last_used_at' => 'datetime',
    ];

    public function usuario()
    {
        return $this->belongsTo(Usuario::class);
    }

    /**
     * Genera token nuevo. Retorna ['plain' => string, 'model' => ApiToken].
     * El plain text solo existe en este momento — DB guarda el hash.
     *
     * @return array{plain: string, model: self}
     */
    public static function generate(Usuario $usuario): array
    {
        static::where('usuario_id', $usuario->id)
            ->where('expires_at', '<', now())
            ->delete();

        $plain = Str::random(64);

        $model = static::create([
            'usuario_id' => $usuario->id,
            'token'      => hash('sha256', $plain),
            'expires_at' => now()->addHours(24),
        ]);

        return ['plain' => $plain, 'model' => $model];
    }

    public static function findValid(string $plain): ?self
    {
        $hashed = hash('sha256', $plain);

        $token = static::where('token', $hashed)
            ->where('expires_at', '>', now())
            ->with('usuario')
            ->first();

        if ($token) {
            $token->update(['last_used_at' => now()]);
        }

        return $token;
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }
}
