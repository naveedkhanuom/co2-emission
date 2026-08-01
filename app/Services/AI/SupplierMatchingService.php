<?php

namespace App\Services\AI;

use App\Models\Supplier;
use Illuminate\Support\Str;

/**
 * Phase 3: link an extracted Scope 3 line to an existing supplier.
 *
 * Supplier queries are company-scoped (HasCompanyScope), so this only ever
 * matches within the current tenant. Exact name wins; otherwise a contained
 * match on a distinctive name is accepted. Ambiguous matches are declined —
 * a wrong supplier link is worse than none.
 */
class SupplierMatchingService
{
    /**
     * @return array{supplier_id:int, name:string}|null
     */
    public function match(?string $name): ?array
    {
        $name = trim((string) $name);
        if ($name === '' || Str::length($name) < 3) {
            return null;
        }

        $lower = Str::lower($name);

        // 1. Exact (case-insensitive) name.
        $exact = Supplier::whereRaw('LOWER(name) = ?', [$lower])->first();
        if ($exact) {
            return ['supplier_id' => $exact->id, 'name' => $exact->name];
        }

        // 2. Contained match — but only when it is unambiguous (exactly one hit),
        //    so "Emirates" doesn't silently pick one of several.
        $candidates = Supplier::where(function ($q) use ($lower) {
            $q->whereRaw('LOWER(name) LIKE ?', ['%' . $lower . '%'])
              ->orWhereRaw('? LIKE CONCAT(\'%\', LOWER(name), \'%\')', [$lower]);
        })->limit(2)->get();

        if ($candidates->count() === 1) {
            $s = $candidates->first();
            return ['supplier_id' => $s->id, 'name' => $s->name];
        }

        return null;
    }
}
