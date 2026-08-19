<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportElabelDataCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'elabel:import-data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import all legacy data from elabel database into db_sipat_terpadu elabel_* tables';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting eLabel Data Migration...');

        // 1. Build User Mapping Map (elabel user_id -> sipat_terpadu user_id)
        $this->info('Mapping users...');
        $legacyUsers = DB::connection('mysql')->select("SELECT * FROM elabel.users");
        $defaultAdminId = DB::table('users')->value('id') ?? 1;
        $userMap = [];

        foreach ($legacyUsers as $lUser) {
            $matchedId = DB::table('users')
                ->where('email', $lUser->email)
                ->value('id');

            if ($matchedId) {
                $userMap[$lUser->id] = $matchedId;
            } else {
                $userMap[$lUser->id] = $defaultAdminId;
            }
        }

        $resolveUser = function ($oldId) use ($userMap, $defaultAdminId) {
            if (!$oldId) return $defaultAdminId;
            return $userMap[$oldId] ?? $defaultAdminId;
        };

        // Disable Foreign Key checks temporarily for clean bulk insert
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        try {
            // 1. elabel_boxes
            $this->info('Migrating boxes...');
            DB::table('elabel_boxes')->truncate();
            $boxes = DB::connection('mysql')->select("SELECT * FROM elabel.boxes");
            foreach ($boxes as $row) {
                DB::table('elabel_boxes')->insert([
                    'id'           => $row->id,
                    'created_by'   => $resolveUser($row->created_by),
                    'created_at'   => $row->created_at,
                    'updated_at'   => $row->updated_at,
                    'box_code'     => $row->box_code,
                    'location'     => $row->location,
                    'vehicle_type' => $row->vehicle_type,
                ]);
            }
            $this->info('Migrated ' . count($boxes) . ' boxes.');

            // 2. elabel_box_years
            $this->info('Migrating box_years...');
            DB::table('elabel_box_years')->truncate();
            $boxYears = DB::connection('mysql')->select("SELECT * FROM elabel.box_years");
            foreach ($boxYears as $row) {
                DB::table('elabel_box_years')->insert([
                    'id'     => $row->id,
                    'box_id' => $row->box_id,
                    'year'   => $row->year,
                ]);
            }
            $this->info('Migrated ' . count($boxYears) . ' box_years.');

            // 3. elabel_bpkb
            $this->info('Migrating bpkb...');
            DB::table('elabel_bpkb')->truncate();
            $bpkbRows = DB::connection('mysql')->select("SELECT * FROM elabel.bpkb");
            foreach ($bpkbRows as $row) {
                $pdfPath = $row->pdf_path;
                if ($pdfPath && strpos($pdfPath, 'elabel/') !== 0) {
                    $pdfPath = 'elabel/' . ltrim($pdfPath, '/');
                }

                DB::table('elabel_bpkb')->insert([
                    'id'           => $row->id,
                    'box_id'       => $row->box_id,
                    'year'         => $row->year,
                    'vehicle_type' => $row->vehicle_type,
                    'plate_number' => $row->plate_number,
                    'no_bpkb'      => $row->no_bpkb,
                    'nibar'        => $row->nibar,
                    'no_rangka'    => $row->no_rangka,
                    'no_mesin'     => $row->no_mesin,
                    'merek'        => $row->merek,
                    'tipe'         => $row->tipe,
                    'isi_silinder' => $row->isi_silinder,
                    'warna'        => $row->warna,
                    'pengguna'     => $row->pengguna,
                    'status'       => $row->status,
                    'pdf_path'     => $pdfPath,
                    'input_by'     => $resolveUser($row->input_by),
                    'created_at'   => $row->created_at,
                    'updated_at'   => $row->updated_at,
                ]);
            }
            $this->info('Migrated ' . count($bpkbRows) . ' bpkb records.');

            // 4. elabel_bpkb_deletes
            $this->info('Migrating bpkb_deletes...');
            DB::table('elabel_bpkb_deletes')->truncate();
            $bpkbDeletes = DB::connection('mysql')->select("SELECT * FROM elabel.bpkb_deletes");
            foreach ($bpkbDeletes as $row) {
                $pdfPath = $row->pdf_path;
                if ($pdfPath && strpos($pdfPath, 'elabel/') !== 0) {
                    $pdfPath = 'elabel/' . ltrim($pdfPath, '/');
                }
                $docPath = $row->support_doc_path;
                if ($docPath && strpos($docPath, 'elabel/') !== 0) {
                    $docPath = 'elabel/' . ltrim($docPath, '/');
                }

                DB::table('elabel_bpkb_deletes')->insert([
                    'id'               => $row->id,
                    'bpkb_id'          => $row->bpkb_id,
                    'box_id'           => $row->box_id,
                    'box_code'         => $row->box_code,
                    'year'             => $row->year,
                    'vehicle_type'     => $row->vehicle_type,
                    'plate_number'     => $row->plate_number,
                    'no_bpkb'          => $row->no_bpkb,
                    'nibar'            => $row->nibar,
                    'no_rangka'        => $row->no_rangka,
                    'no_mesin'         => $row->no_mesin,
                    'merek'            => $row->merek,
                    'tipe'             => $row->tipe,
                    'isi_silinder'     => $row->isi_silinder,
                    'warna'            => $row->warna,
                    'pengguna'         => $row->pengguna,
                    'status'           => $row->status,
                    'pdf_path'         => $pdfPath,
                    'input_by'         => $row->input_by ? $resolveUser($row->input_by) : null,
                    'deleted_by'       => $resolveUser($row->deleted_by),
                    'deleted_at'       => $row->deleted_at,
                    'reason'           => $row->reason,
                    'reason_detail'    => $row->reason_detail,
                    'support_doc_path' => $docPath,
                ]);
            }
            $this->info('Migrated ' . count($bpkbDeletes) . ' bpkb_deletes records.');

            // 5. elabel_sertifikat_boxes
            $this->info('Migrating sertifikat_boxes...');
            DB::table('elabel_sertifikat_boxes')->truncate();
            $sertifikatBoxes = DB::connection('mysql')->select("SELECT * FROM elabel.sertifikat_boxes");
            foreach ($sertifikatBoxes as $row) {
                DB::table('elabel_sertifikat_boxes')->insert([
                    'id'         => $row->id,
                    'box_code'   => $row->box_code,
                    'lokasi'     => $row->lokasi,
                    'created_by' => $row->created_by ? $resolveUser($row->created_by) : null,
                    'created_at' => $row->created_at,
                    'updated_at' => $row->updated_at,
                ]);
            }
            $this->info('Migrated ' . count($sertifikatBoxes) . ' sertifikat_boxes.');

            // 6. elabel_sertifikat_tanah
            $this->info('Migrating sertifikat_tanah...');
            DB::table('elabel_sertifikat_tanah')->truncate();
            $sertifikatRows = DB::connection('mysql')->select("SELECT * FROM elabel.sertifikat_tanah");
            foreach ($sertifikatRows as $row) {
                $pdfPath = $row->pdf_path;
                if ($pdfPath) {
                    $pdfPath = str_replace('uploads/', '', ltrim($pdfPath, '/'));
                    if (strpos($pdfPath, 'elabel/') !== 0) {
                        $pdfPath = 'elabel/' . $pdfPath;
                    }
                }

                DB::table('elabel_sertifikat_tanah')->insert([
                    'id'                => $row->id,
                    'no_sertipikat'     => $row->no_sertipikat,
                    'nibar'             => $row->nibar,
                    'status_penggunaan' => $row->status_penggunaan,
                    'spesifikasi'       => $row->spesifikasi,
                    'luas'              => $row->luas,
                    'tanggal_perolehan' => $row->tanggal_perolehan,
                    'nilai_perolehan'   => $row->nilai_perolehan,
                    'nama_pemilik'      => $row->nama_pemilik,
                    'cara_perolehan'    => $row->cara_perolehan,
                    'alamat'            => $row->alamat,
                    'lokasi'            => $row->lokasi,
                    'dinas'             => $row->dinas,
                    'sync_status'       => $row->sync_status,
                    'data_version'      => $row->data_version,
                    'box_id'            => $row->box_id,
                    'pdf_path'          => $pdfPath,
                    'created_at'        => $row->created_at,
                    'updated_at'        => $row->updated_at,
                ]);
            }
            $this->info('Migrated ' . count($sertifikatRows) . ' sertifikat_tanah.');

            // 7. elabel_surat_penyerahan_boxes
            $this->info('Migrating surat_penyerahan_boxes...');
            DB::table('elabel_surat_penyerahan_boxes')->truncate();
            $spBoxes = DB::connection('mysql')->select("SELECT * FROM elabel.surat_penyerahan_boxes");
            foreach ($spBoxes as $row) {
                DB::table('elabel_surat_penyerahan_boxes')->insert([
                    'id'         => $row->id,
                    'box_code'   => $row->box_code,
                    'lokasi'     => $row->lokasi,
                    'created_by' => $row->created_by ? $resolveUser($row->created_by) : null,
                    'created_at' => $row->created_at,
                    'updated_at' => $row->updated_at,
                ]);
            }
            $this->info('Migrated ' . count($spBoxes) . ' surat_penyerahan_boxes.');

            // 8. elabel_surat_penyerahan
            $this->info('Migrating surat_penyerahan...');
            DB::table('elabel_surat_penyerahan')->truncate();
            $spRows = DB::connection('mysql')->select("SELECT * FROM elabel.surat_penyerahan");
            foreach ($spRows as $row) {
                $pdfPath = $row->pdf_path;
                if ($pdfPath && strpos($pdfPath, 'elabel/') !== 0) {
                    $pdfPath = 'elabel/' . ltrim($pdfPath, '/');
                }

                DB::table('elabel_surat_penyerahan')->insert([
                    'id'                => $row->id,
                    'nibar'             => $row->nibar,
                    'no_surat'          => $row->no_surat,
                    'status_penggunaan' => $row->status_penggunaan,
                    'spesifikasi'       => $row->spesifikasi,
                    'jenis_penyerahan'  => $row->jenis_penyerahan,
                    'luas'              => $row->luas,
                    'tanggal_perolehan' => $row->tanggal_perolehan,
                    'alamat'            => $row->alamat,
                    'lokasi'            => $row->lokasi,
                    'dinas'             => $row->dinas,
                    'pemberi_hibah'     => $row->pemberi_hibah,
                    'pdf_path'          => $pdfPath,
                    'box_id'            => $row->box_id,
                    'created_at'        => $row->created_at,
                    'updated_at'        => $row->updated_at,
                ]);
            }
            $this->info('Migrated ' . count($spRows) . ' surat_penyerahan.');

            // 9. elabel_loans
            $this->info('Migrating loans...');
            DB::table('elabel_loans')->truncate();
            $loans = DB::connection('mysql')->select("SELECT * FROM elabel.loans");
            foreach ($loans as $row) {
                DB::table('elabel_loans')->insert([
                    'id'                => $row->id,
                    'bpkb_id'           => $row->bpkb_id,
                    'requester_id'      => $row->requester_id ? $resolveUser($row->requester_id) : null,
                    'requester_name'    => $row->requester_name,
                    'requester_phone'   => $row->requester_phone,
                    'requester_email'   => $row->requester_email,
                    'requester_org'     => $row->requester_org,
                    'requester_address' => $row->requester_address,
                    'requester_note'    => $row->requester_note,
                    'requested_at'      => $row->requested_at,
                    'approved_by'       => $row->approved_by ? $resolveUser($row->approved_by) : null,
                    'approved_at'       => $row->approved_at,
                    'status'            => $row->status,
                    'note'              => $row->note,
                    'created_at'        => $row->created_at,
                    'updated_at'        => $row->updated_at,
                ]);
            }
            $this->info('Migrated ' . count($loans) . ' loans.');

            // 10. elabel_loan_histories
            $this->info('Migrating loan_histories...');
            DB::table('elabel_loan_histories')->truncate();
            $histories = DB::connection('mysql')->select("SELECT * FROM elabel.loan_histories");
            foreach ($histories as $row) {
                DB::table('elabel_loan_histories')->insert([
                    'id'         => $row->id,
                    'loan_id'    => $row->loan_id,
                    'status'     => $row->status,
                    'changed_by' => $row->changed_by ? $resolveUser($row->changed_by) : null,
                    'changed_at' => $row->changed_at,
                    'note'       => $row->note,
                ]);
            }
            $this->info('Migrated ' . count($histories) . ' loan_histories.');

            // 11. elabel_activity_logs
            $this->info('Migrating activity_logs...');
            DB::table('elabel_activity_logs')->truncate();
            $activityLogs = DB::connection('mysql')->select("SELECT * FROM elabel.activity_logs");
            foreach ($activityLogs as $row) {
                DB::table('elabel_activity_logs')->insert([
                    'id'             => $row->id,
                    'user_id'        => $row->user_id ? $resolveUser($row->user_id) : null,
                    'action'         => $row->action,
                    'module'         => $row->module,
                    'description'    => $row->description,
                    'reference_type' => $row->reference_type,
                    'reference_id'   => $row->reference_id,
                    'ip_address'     => $row->ip_address,
                    'user_agent'     => $row->user_agent,
                    'created_at'     => $row->created_at,
                ]);
            }
            $this->info('Migrated ' . count($activityLogs) . ' activity_logs.');

        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }

        $this->info('eLabel Data Migration Completed Successfully!');
    }
}
