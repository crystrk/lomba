<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BulkStoreParticipantRequest;
use App\Http\Requests\Admin\StoreParticipantRequest;
use App\Http\Requests\Admin\UpdateParticipantRequest;
use App\Models\Competition;
use App\Models\Participant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Response;
use Inertia\ResponseFactory;

class ParticipantController extends Controller
{
    public function index(Competition $competition): Response|ResponseFactory
    {
        $participants = $competition->participants()
            ->orderBy('name')
            ->get()
            ->map(fn (Participant $p) => [
                'id' => $p->id,
                'name' => $p->name,
                'short_name' => $p->short_name,
                'logo_url' => $p->logo_path ? Storage::url($p->logo_path) : null,
                'draw_position' => $p->draw_position,
            ]);

        return Inertia('Admin/Competitions/Participants/Index', [
            'competition' => [
                'id' => $competition->id,
                'name' => $competition->name,
                'status' => $competition->status->value,
            ],
            'participants' => $participants,
        ]);
    }

    public function create(Competition $competition): Response|ResponseFactory
    {
        return Inertia('Admin/Competitions/Participants/Create', [
            'competition' => [
                'id' => $competition->id,
                'name' => $competition->name,
            ],
        ]);
    }

    public function store(StoreParticipantRequest $request, Competition $competition): RedirectResponse
    {
        $data = $request->safe()->except('logo');

        if ($request->hasFile('logo')) {
            $data['logo_path'] = $request->file('logo')
                ->storePublicly('logos', 'public');
        }

        $data['competition_id'] = $competition->id;

        $competition->participants()->create($data);

        return redirect()->route('admin.competitions.participants.index', $competition)
            ->with('success', 'Peserta berhasil ditambahkan.');
    }

    public function bulkStore(BulkStoreParticipantRequest $request, Competition $competition): RedirectResponse
    {
        if (! $competition->isEditable()) {
            abort(403, 'Peserta tidak dapat ditambah pada lomba yang sudah terkunci.');
        }

        $lines = preg_split('/\r\n|\r|\n/', $request->input('raw_names'));
        $count = 0;

        foreach ($lines as $line) {
            $name = trim($line);
            if ($name === '') {
                continue;
            }

            $shortName = $this->generateShortName($name);

            $competition->participants()->create([
                'name' => $name,
                'short_name' => $shortName,
            ]);

            $count++;
        }

        if ($count === 0) {
            return redirect()->back()->withErrors(['raw_names' => 'Tidak ada nama peserta valid yang dimasukkan.']);
        }

        return redirect()->route('admin.competitions.participants.index', $competition)
            ->with('success', "Berhasil menambahkan {$count} peserta.");
    }

    private function generateShortName(string $name): ?string
    {
        $words = preg_split('/\s+/', trim($name));
        if (count($words) >= 3) {
            $short = mb_strtoupper(mb_substr($words[0], 0, 1).mb_substr($words[1], 0, 1).mb_substr($words[2], 0, 1));
        } elseif (count($words) === 2) {
            $short = mb_strtoupper(mb_substr($words[0], 0, 2).mb_substr($words[1], 0, 1));
        } else {
            $short = mb_strtoupper(mb_substr($name, 0, 3));
        }

        return mb_strlen($short) >= 2 ? $short : null;
    }

    public function edit(Competition $competition, Participant $participant): Response|ResponseFactory
    {
        return Inertia('Admin/Competitions/Participants/Edit', [
            'competition' => [
                'id' => $competition->id,
                'name' => $competition->name,
            ],
            'participant' => [
                'id' => $participant->id,
                'name' => $participant->name,
                'short_name' => $participant->short_name,
                'logo_url' => $participant->logo_path ? Storage::url($participant->logo_path) : null,
            ],
        ]);
    }

    public function update(UpdateParticipantRequest $request, Competition $competition, Participant $participant): RedirectResponse
    {
        $data = $request->safe()->except('logo');

        if ($request->hasFile('logo')) {
            if ($participant->logo_path) {
                Storage::disk('public')->delete($participant->logo_path);
            }

            $data['logo_path'] = $request->file('logo')
                ->storePublicly('logos', 'public');
        }

        $participant->update($data);

        return redirect()->route('admin.competitions.participants.index', $competition)
            ->with('success', 'Peserta berhasil diperbarui.');
    }

    public function destroy(Competition $competition, Participant $participant): RedirectResponse
    {
        if (! $competition->isEditable()) {
            abort(403, 'Peserta tidak dapat diubah pada lomba yang sudah terkunci.');
        }

        if ($participant->logo_path) {
            Storage::disk('public')->delete($participant->logo_path);
        }

        $participant->delete();

        return redirect()->route('admin.competitions.participants.index', $competition)
            ->with('success', 'Peserta berhasil dihapus.');
    }
}
