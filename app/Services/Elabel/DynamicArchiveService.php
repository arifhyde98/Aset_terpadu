<?php

namespace App\Services\Elabel;

use App\Models\Elabel\Dynamic\ArchiveAttachment;
use App\Models\Elabel\Dynamic\ArchiveBox;
use App\Models\Elabel\Dynamic\ArchiveItem;
use App\Models\Elabel\Dynamic\ArchiveType;
use App\Models\Elabel\ElabelActivityLog;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DynamicArchiveService
{
    /**
     * Simpan Dokumen Arsip Dinamis Baru
     */
    public function createItem(ArchiveType $type, array $data, ?UploadedFile $pdfFile = null, array $attachments = []): ArchiveItem
    {
        return DB::transaction(function () use ($type, $data, $pdfFile, $attachments) {
            $metadata = $data['metadata'] ?? [];

            // Tangani file scan PDF utama jika diunggah
            $pdfPath = null;
            if ($pdfFile && $pdfFile->isValid()) {
                $pdfPath = $this->storeUploadedFile($pdfFile, 'dynamic/' . strtolower($type->kode), $data['nomor_dokumen'] ?? 'arsip');
            }

            $item = ArchiveItem::create([
                'archive_type_id' => $type->id,
                'archive_box_id'  => !empty($data['archive_box_id']) ? (int) $data['archive_box_id'] : null,
                'opd_id'          => !empty($data['opd_id']) ? (int) $data['opd_id'] : null,
                'nomor_dokumen'   => trim($data['nomor_dokumen']),
                'nama_dokumen'    => trim($data['nama_dokumen']),
                'tahun_dokumen'   => !empty($data['tahun_dokumen']) ? (int) $data['tahun_dokumen'] : null,
                'metadata'        => $metadata,
                'file_scan_pdf'   => $pdfPath,
                'status'          => $data['status'] ?? 'Tersedia',
                'keterangan'      => $data['keterangan'] ?? null,
                'input_by'        => Auth::id(),
            ]);

            // Tangani lampiran tambahan / file field dinamis
            if (!empty($attachments)) {
                foreach ($attachments as $key => $file) {
                    if ($file instanceof UploadedFile && $file->isValid()) {
                        $storedPath = $this->storeUploadedFile($file, 'dynamic/' . strtolower($type->kode) . '/attachments', 'att_' . $key);
                        
                        ArchiveAttachment::create([
                            'archive_item_id' => $item->id,
                            'field_name'      => is_string($key) ? $key : null,
                            'file_title'      => $file->getClientOriginalName(),
                            'file_path'       => $storedPath,
                            'file_type'       => strtolower($file->getClientOriginalExtension()),
                            'file_size'       => $file->getSize(),
                        ]);
                    }
                }
            }

            $this->logActivity('create', 'Arsip ' . $type->nama, "Menambahkan arsip {$type->kode}: {$item->nomor_dokumen} - {$item->nama_dokumen}", 'archive_item', $item->id);

            return $item;
        });
    }

    /**
     * Perbarui Dokumen Arsip Dinamis
     */
    public function updateItem(ArchiveItem $item, array $data, ?UploadedFile $pdfFile = null, array $attachments = []): ArchiveItem
    {
        return DB::transaction(function () use ($item, $data, $pdfFile, $attachments) {
            $type = $item->archiveType;
            $metadata = $data['metadata'] ?? ($item->metadata ?? []);

            $updatePayload = [
                'archive_box_id' => !empty($data['archive_box_id']) ? (int) $data['archive_box_id'] : null,
                'opd_id'         => !empty($data['opd_id']) ? (int) $data['opd_id'] : null,
                'nomor_dokumen'  => trim($data['nomor_dokumen']),
                'nama_dokumen'   => trim($data['nama_dokumen']),
                'tahun_dokumen'  => !empty($data['tahun_dokumen']) ? (int) $data['tahun_dokumen'] : null,
                'metadata'       => $metadata,
                'status'         => $data['status'] ?? $item->status,
                'keterangan'     => $data['keterangan'] ?? null,
            ];

            // Jika ada PDF baru
            if ($pdfFile && $pdfFile->isValid()) {
                if ($item->file_scan_pdf && Storage::disk('public')->exists($item->file_scan_pdf)) {
                    Storage::disk('public')->delete($item->file_scan_pdf);
                }
                $updatePayload['file_scan_pdf'] = $this->storeUploadedFile($pdfFile, 'dynamic/' . strtolower($type->kode), $data['nomor_dokumen'] ?? 'arsip');
            }

            $item->update($updatePayload);

            // Jika ada lampiran baru yang diunggah
            if (!empty($attachments)) {
                foreach ($attachments as $key => $file) {
                    if ($file instanceof UploadedFile && $file->isValid()) {
                        // Jika field_name sudah ada lampirannya dan berniat ditimpa
                        if (is_string($key)) {
                            $oldAtt = ArchiveAttachment::where('archive_item_id', $item->id)
                                ->where('field_name', $key)
                                ->first();
                            if ($oldAtt) {
                                if (Storage::disk('public')->exists($oldAtt->file_path)) {
                                    Storage::disk('public')->delete($oldAtt->file_path);
                                }
                                $oldAtt->delete();
                            }
                        }

                        $storedPath = $this->storeUploadedFile($file, 'dynamic/' . strtolower($type->kode) . '/attachments', 'att_' . $key);
                        ArchiveAttachment::create([
                            'archive_item_id' => $item->id,
                            'field_name'      => is_string($key) ? $key : null,
                            'file_title'      => $file->getClientOriginalName(),
                            'file_path'       => $storedPath,
                            'file_type'       => strtolower($file->getClientOriginalExtension()),
                            'file_size'       => $file->getSize(),
                        ]);
                    }
                }
            }

            $this->logActivity('update', 'Arsip ' . $type->nama, "Memperbarui arsip {$type->kode}: {$item->nomor_dokumen}", 'archive_item', $item->id);

            return $item;
        });
    }

    /**
     * Hapus Dokumen Arsip Dinamis beserta seluruh berkas fisiknya
     */
    public function deleteItem(ArchiveItem $item): bool
    {
        return DB::transaction(function () use ($item) {
            $type = $item->archiveType;
            $docNo = $item->nomor_dokumen;

            // Hapus berkas scan PDF utama
            if ($item->file_scan_pdf && Storage::disk('public')->exists($item->file_scan_pdf)) {
                Storage::disk('public')->delete($item->file_scan_pdf);
            }

            // Hapus seluruh file lampiran
            foreach ($item->attachments as $att) {
                if ($att->file_path && Storage::disk('public')->exists($att->file_path)) {
                    Storage::disk('public')->delete($att->file_path);
                }
                $att->delete();
            }

            $item->delete();

            $this->logActivity('delete', 'Arsip ' . ($type->nama ?? 'Dinamis'), "Menghapus arsip: {$docNo}", 'archive_item', $item->id);

            return true;
        });
    }

    /**
     * Generate Kode Box Otomatis
     */
    public function generateNextBoxCode(ArchiveType $type): string
    {
        $count = ArchiveBox::where('archive_type_id', $type->id)->count() + 1;
        $prefix = 'BOX-' . strtoupper($type->kode);
        $padded = str_pad($count, 3, '0', STR_PAD_LEFT);
        
        $candidate = "{$prefix}-{$padded}";
        $attempt = 1;
        while (ArchiveBox::where('nomor_box', $candidate)->exists()) {
            $attempt++;
            $padded = str_pad($count + $attempt - 1, 3, '0', STR_PAD_LEFT);
            $candidate = "{$prefix}-{$padded}";
        }

        return $candidate;
    }

    /**
     * Helper simpan file ke storage public
     */
    public function storeUploadedFile(UploadedFile $file, string $subfolder, string $prefix = 'doc'): string
    {
        $cleanPrefix = preg_replace('/[^A-Za-z0-9]+/', '-', $prefix) ?: 'file';
        $cleanPrefix = substr(trim($cleanPrefix, '-'), 0, 50);
        $extension = strtolower($file->getClientOriginalExtension()) ?: 'dat';
        
        $filename = $cleanPrefix . '_' . time() . '_' . substr(uniqid(), -4) . '.' . $extension;
        $path = $subfolder . '/' . $filename;

        $file->storeAs($subfolder, $filename, 'public');

        return $path;
    }

    /**
     * Helper log aktivitas
     */
    public function logActivity(string $action, string $module, string $description, ?string $refType = null, ?int $refId = null): void
    {
        ElabelActivityLog::create([
            'user_id'        => Auth::id() ?: 1,
            'action'         => $action,
            'module'         => $module,
            'description'    => $description,
            'reference_type' => $refType,
            'reference_id'   => $refId,
            'ip_address'     => request()->ip(),
            'user_agent'     => request()->userAgent(),
            'created_at'     => now(),
        ]);
    }
}
