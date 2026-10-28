<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\TelefonoTicketService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TelefonoTicketController extends Controller
{
    public function __construct(private readonly TelefonoTicketService $service)
    {
        $this->middleware('can:configuraciones_list')->only(['index', 'show']);
        $this->middleware('can:configuraciones_edit')->only('update');
    }

    public function index(): View
    {
        return view('telefonos-ticket.index', ['settings' => $this->service->current()]);
    }

    public function show(): JsonResponse
    {
        return response()->json($this->service->current());
    }

    public function update(Request $request): JsonResponse
    {
        foreach (['telefono_1', 'telefono_2'] as $field) {
            if (is_string($request->input($field))) {
                $value = trim($request->input($field));
                $request->merge([$field => $value === '' ? null : $value]);
            }
        }
        $format = 'regex:/\A(?=.*[0-9])[+0-9 ()-]+\z/';
        $data = $request->validate([
            'telefono_1' => ['required', 'string', 'max:25', $format],
            'telefono_2' => ['nullable', 'string', 'max:25', $format],
        ], ['*.regex' => 'Use números, espacios, +, guiones o paréntesis e incluya al menos un dígito.']);
        $this->service->update($data);

        return response()->json(['success' => true, 'message' => 'Teléfonos del ticket actualizados correctamente.', 'settings' => $this->service->current()]);
    }
}
