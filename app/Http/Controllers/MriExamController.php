<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMriExamRequest;
use App\Http\Requests\UpdateMriExamRequest;
use App\Models\MriExam;
use App\Models\Patient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class MriExamController extends Controller
{
    /**
     * Store a newly created MRI examination in storage.
     */
    public function store(StoreMriExamRequest $request, Patient $patient): RedirectResponse
    {
        $validated = $request->validated();

        $uploadedImages = [];
        if ($request->hasFile('images')) {
            /** @var array<UploadedFile> $files */
            $files = $request->file('images');
            foreach ($files as $file) {
                $path = $file->store("mri_files/{$patient->id}", 'public');
                $uploadedImages[] = [
                    'name' => $file->getClientOriginalName(),
                    'path' => '/storage/'.$path,
                    'size' => $file->getSize(),
                    'mime' => $file->getClientMimeType(),
                ];
            }
        }

        $mri = $patient->mriExams()->create([
            'exam_date' => $validated['exam_date'],
            'timing' => $validated['timing'],
            'region' => $validated['region'],
            'sep_protocol_applied' => $request->boolean('sep_protocol_applied', true),
            'sequences_available' => $validated['sequences_available'] ?? null,
            'lesions_count_estimated' => $validated['lesions_count_estimated'] ?? null,
            'lesions_locations' => $validated['lesions_locations'] ?? null,
            'contrast_enhancement' => $validated['contrast_enhancement'] ?? 'negative',
            'new_lesions_count' => $validated['new_lesions_count'] ?? 0,
            'radiologist_report' => $validated['radiologist_report'] ?? null,
            'dicom_study_uid' => $validated['dicom_study_uid'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'key_images' => ! empty($uploadedImages) ? $uploadedImages : null,
        ]);

        $dateFormatted = $mri->exam_date?->format('d/m/Y') ?? '';
        $regionLabel = match ($mri->region) {
            'cerebrale' => 'cérébrale',
            'medullaire' => 'médullaire',
            'les_deux' => 'cérébro-médullaire',
            default => 'IRM',
        };

        return redirect()
            ->route('patients.show', $patient->id)
            ->with('success', "L'IRM {$regionLabel} du {$dateFormatted} a été enregistrée avec succès.");
    }

    /**
     * Update the specified MRI examination in storage.
     */
    public function update(UpdateMriExamRequest $request, Patient $patient, MriExam $mriExam): RedirectResponse
    {
        abort_if($mriExam->patient_id !== $patient->id, 404);

        $validated = $request->validated();

        $currentImages = is_array($mriExam->key_images) ? $mriExam->key_images : [];

        // Determine which existing images are retained
        $retainedImages = [];
        if ($request->has('retained_images')) {
            $rawRetained = $request->input('retained_images');
            if (is_string($rawRetained)) {
                $decoded = json_decode($rawRetained, true);
                if (is_array($decoded)) {
                    $retainedImages = $decoded;
                }
            } elseif (is_array($rawRetained)) {
                $retainedImages = $rawRetained;
            }
        } else {
            $retainedImages = $currentImages;
        }

        // Physically delete files that were removed
        $retainedPaths = array_column($retainedImages, 'path');
        foreach ($currentImages as $curr) {
            if (isset($curr['path']) && ! in_array($curr['path'], $retainedPaths, true)) {
                $storagePath = str_replace('/storage/', '', $curr['path']);
                Storage::disk('public')->delete($storagePath);
            }
        }

        // Append any newly uploaded images
        if ($request->hasFile('images')) {
            /** @var array<UploadedFile> $files */
            $files = $request->file('images');
            foreach ($files as $file) {
                $path = $file->store("mri_files/{$patient->id}", 'public');
                $retainedImages[] = [
                    'name' => $file->getClientOriginalName(),
                    'path' => '/storage/'.$path,
                    'size' => $file->getSize(),
                    'mime' => $file->getClientMimeType(),
                ];
            }
        }

        $mriExam->update([
            'exam_date' => $validated['exam_date'],
            'timing' => $validated['timing'],
            'region' => $validated['region'],
            'sep_protocol_applied' => $request->boolean('sep_protocol_applied', true),
            'sequences_available' => $validated['sequences_available'] ?? null,
            'lesions_count_estimated' => $validated['lesions_count_estimated'] ?? null,
            'lesions_locations' => $validated['lesions_locations'] ?? null,
            'contrast_enhancement' => $validated['contrast_enhancement'] ?? 'negative',
            'new_lesions_count' => $validated['new_lesions_count'] ?? 0,
            'radiologist_report' => $validated['radiologist_report'] ?? null,
            'dicom_study_uid' => $validated['dicom_study_uid'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'key_images' => ! empty($retainedImages) ? array_values($retainedImages) : null,
        ]);

        $dateFormatted = $mriExam->exam_date?->format('d/m/Y') ?? '';
        $regionLabel = match ($mriExam->region) {
            'cerebrale' => 'cérébrale',
            'medullaire' => 'médullaire',
            'les_deux' => 'cérébro-médullaire',
            default => 'IRM',
        };

        return redirect()
            ->route('patients.show', $patient->id)
            ->with('success', "L'IRM {$regionLabel} du {$dateFormatted} a été mise à jour avec succès.");
    }

    /**
     * Remove a specific key image from an MRI exam.
     */
    public function destroyImage(Patient $patient, MriExam $mriExam, int $imageIndex): RedirectResponse
    {
        abort_if($mriExam->patient_id !== $patient->id, 404);

        $currentImages = is_array($mriExam->key_images) ? $mriExam->key_images : [];

        if (isset($currentImages[$imageIndex])) {
            $deleted = $currentImages[$imageIndex];
            if (isset($deleted['path'])) {
                $storagePath = str_replace('/storage/', '', $deleted['path']);
                Storage::disk('public')->delete($storagePath);
            }
            array_splice($currentImages, $imageIndex, 1);
            $mriExam->update([
                'key_images' => ! empty($currentImages) ? array_values($currentImages) : null,
            ]);
        }

        return back()->with('success', 'La coupe sélectionnée a été supprimée avec succès.');
    }

    /**
     * Remove the specified MRI examination from storage.
     */
    public function destroy(Patient $patient, MriExam $mriExam): RedirectResponse
    {
        abort_if($mriExam->patient_id !== $patient->id, 404);

        // Delete associated files from storage
        if (is_array($mriExam->key_images)) {
            foreach ($mriExam->key_images as $img) {
                if (isset($img['path'])) {
                    $storagePath = str_replace('/storage/', '', $img['path']);
                    Storage::disk('public')->delete($storagePath);
                }
            }
        }

        $dateFormatted = $mriExam->exam_date?->format('d/m/Y') ?? '';
        $mriExam->delete();

        return redirect()
            ->route('patients.show', $patient->id)
            ->with('success', "L'examen IRM du {$dateFormatted} a été supprimé avec succès.");
    }
}
