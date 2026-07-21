<?php

namespace App\Http\Controllers;

use App\Services\UnitConverter;
use Illuminate\Http\Request;

/**
 * Thin HTTP wrapper around UnitConverter so entry forms can normalise the
 * activity value a user typed (e.g. "5 MWh") to the unit the chosen emission
 * factor expects (e.g. kWh) before computing CO2e.
 */
class UnitConversionController extends Controller
{
    public function __construct(private UnitConverter $converter)
    {
        $this->middleware('auth');
    }

    /**
     * GET /units — supported units grouped by dimension (for dropdowns).
     */
    public function units()
    {
        return response()->json([
            'success' => true,
            'units'   => $this->converter->units(),
        ]);
    }

    /**
     * POST /units/convert — convert a value between two units.
     *
     * Returns the converted value plus whether the units were compatible, so the
     * caller can warn the user about a mismatch rather than silently miscalculate.
     */
    public function convert(Request $request)
    {
        $validated = $request->validate([
            'value' => 'required|numeric',
            'from'  => 'required|string|max:50',
            'to'    => 'required|string|max:50',
        ]);

        if (!$this->converter->areCompatible($validated['from'], $validated['to'])) {
            return response()->json([
                'success'    => false,
                'compatible' => false,
                'message'    => "“{$validated['from']}” and “{$validated['to']}” are different kinds of measurement and can't be converted.",
            ], 422);
        }

        $converted = $this->converter->convert(
            (float) $validated['value'],
            $validated['from'],
            $validated['to']
        );

        return response()->json([
            'success'    => true,
            'compatible' => true,
            'value'      => (float) $validated['value'],
            'from'       => $validated['from'],
            'to'         => $validated['to'],
            'converted'  => round($converted, 6),
        ]);
    }
}
