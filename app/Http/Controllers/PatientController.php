<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePatientRequest;
use App\Http\Requests\UpdatePatientRequest;
use App\Models\Patient;
use App\Services\PatientService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Afiliación de pacientes. Los permisos se validan en las rutas.
 * No hay eliminación física (RN-06): un paciente se desactiva.
 */
class PatientController extends Controller
{
    public function __construct(private PatientService $patients)
    {
    }

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q'));

        // Un solo campo de búsqueda: número de documento, nombres o apellidos.
        $patients = Patient::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('document_number', 'like', "%{$search}%")
                        ->orWhere('first_names', 'like', "%{$search}%")
                        ->orWhere('last_names', 'like', "%{$search}%");
                });
            })
            // El último afiliado primero (mismo orden que affiliation_code, que se genera del id).
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return view('patients.index', compact('patients', 'search'));
    }

    public function show(Request $request, Patient $patient): View
    {
        // Historial de ventas solo con permiso de ventas, filtrado con la misma regla que el listado (RN-12).
        $user = $request->user();
        $sales = $user->canAny(['ventas.ver', 'ventas.ver_todas'])
            ? $patient->sales()->visibleTo($user)->with('user')->orderByDesc('id')->get()
            : null;

        return view('patients.show', compact('patient', 'sales'));
    }

    public function create(): View
    {
        return view('patients.create', ['patient' => new Patient()]);
    }

    public function store(StorePatientRequest $request): RedirectResponse
    {
        $patient = $this->patients->create($request->validated());

        return redirect()->route('patients.show', $patient)
            ->with('status', "Paciente afiliado con el código {$patient->affiliation_code}.");
    }

    public function edit(Patient $patient): View
    {
        return view('patients.edit', compact('patient'));
    }

    public function update(UpdatePatientRequest $request, Patient $patient): RedirectResponse
    {
        $this->patients->update($patient, $request->validated());

        return redirect()->route('patients.show', $patient)->with('status', 'Paciente actualizado correctamente.');
    }

    // Solo cambia el estado; el registro nunca se elimina.
    public function deactivate(Patient $patient): RedirectResponse
    {
        $patient->update(['status' => 'INACTIVO']);

        return back()->with('status', 'Paciente desactivado.');
    }

    public function activate(Patient $patient): RedirectResponse
    {
        $patient->update(['status' => 'ACTIVO']);

        return back()->with('status', 'Paciente activado.');
    }
}
