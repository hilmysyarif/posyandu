<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;

/**
 * AUTH
 */
use App\User;
use Auth;

/**
 * Requests
 */
use App\Http\Requests\BalitaRequest;
use App\Http\Requests\PeriksaBalitaRequest;

/**
 * Models
 */
use App\DataBalita;
use App\Periksa;
use App\BBU;
use App\TBU;
use App\BBTB1;
use App\BBTB2;
use App\Score;

class BalitaController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');

        $this->middleware('role:visitor');

        $user = User::findOrFail(Auth::user()->id);

        view()->share('user', $user);
    }

    public function dashboard()
    {
        $jml_balita = DataBalita::all()->count(); 

        return view('visitor.index', compact('jml_balita'));

    }
    /**
     * [index] menampilkan data pada halaman index
     */
    public function index()
    {
        /**
         * mengambil data balita melalui model DataBalita 
         * dari table data_balita
         */
        $data_balita = DataBalita::get();

        return view('visitor.balita.index', compact('data_balita'));
    }

    /**
     * [doTambah] proses untuk mela
     */
    public function doTambah(BalitaRequest $request)
    {
        /**
         * Variabel temporary dari inputan form
         */
        $input = $request->all();

        /**
         * Insert data kedalam table data_balita melalui model
         */
        DataBalita::create($input);

        return redirect()->route('data-balita');
    }

    /**
     * [detailBalita]
     * Menampilkan detail dari balita
     */
    public function detailBalita($id)
    {
        /**
         * Mengambil data balita dengan parameter id
         */
        $data_balita = DataBalita::findOrFail($id);

        /**
         * mengambil data score dan semua relasinya
         */
        $score = Score::with('dataBalita')->with('periksa')->orderBy('id', 'DESC')->where('id_balita', $id)->first();
        
        $grafik_score = Score::orderBy('id', 'ASC')->where('id_balita', $id)->get();

        return view('visitor.balita.detail', compact('data_balita', 'score', 'grafik_score'));
    }

    /**
     * [ubahBalita description]
     * manampilkan data pda form update data balita
     */
    public function ubahBalita($id)
    {
        /**
         * [$data_balita description]
         * mengambil data balita yang akan diupdate
         * berdasarkan parameter id
         */
        $data_balita = DataBalita::findOrFail($id);

        return view('visitor.balita.ubah', compact('data_balita'));
        
    }

    /**
     * [doUbahBalita description]
     * proses update data balita
     */
    public function doUbahBalita(BalitaRequest $request, $id)
    {
        /**
         * [$data_balita description]
         * Temporary variabel dari form update
         */
        $data_balita = $request->all();

        /**
         * [$data_balita_db description]
         * ambil data dari table balita yang akan di update
         */
        $data_balita_db = DataBalita::findOrFail($id);

        /**
         * proses update data
         */
        $data_balita_db->update($data_balita);

        return redirect()->route('data-balita');
    }

    /**
     * [destroyBalita description]
     * menghapus data balita
     */
    public function destroyBalita($id)
    {  
        /**
         * [$data_balita description]
         * ambil data yang akan di update berdasarkan id balita
         */
        $data_balita = DataBalita::findOrFail($id);

        /**
         * proses delete
         */
        $data_balita->delete();

        return redirect()->route('data-balita');
    }

    /**
     * proses pemeriksaan
     */
    public function doPeriksaBalita(PeriksaBalitaRequest $request)
    {
        /**
         * temporary variabel dari form periksa
         */
        $input = $request->all();

        $date = date_create($input['tgl_periksa']);

        $input['tgl_periksa'] = date_format($date, 'd-m-Y');

        $id_balita = $input['id_balita'];

        Periksa::create($input);

        $this->hitungBalita($id_balita);

        return redirect()->back();
    }

    public function hitungBalita($id_balita)
    {
        /**
         * mengambil data dari table periksa dan balita berdasarkan reasi one to many
         */
        $periksa_balita = Periksa::with('dataBalita')->where('id_balita',$id_balita)->orderBy('id', 'DESC')->first();

        $time=strtotime($periksa_balita->tgl_periksa);

        $month=date("F",$time);

        /**
         * Hitung umur balita
         */
        $umur = (strtotime($periksa_balita->tgl_periksa) - strtotime($periksa_balita->dataBalita->tgl_lahir)) / (60 * 60 * 24 * 30);

        /**
         * pembulatan umur
         */
        $umur_bulat = floor($umur);

        $this->perhitunganScore($periksa_balita, $umur_bulat, $month);

        // return "Oke";
    }

    public function perhitunganScore($periksa_balita, $umur_bulat, $month)
    {
        /**
         * HITUNG BBU
         */
        $bbu = BBU::where('jk', $periksa_balita->dataBalita->jenis_kelamin)->where('umur', $umur_bulat)->first();

        if ($periksa_balita->berat_badan < $bbu['median']) {

            $nsbr = $bbu['median'] - $bbu['sdmin1'];

        }else{

            $nsbr = $bbu['sdplus1'] - $bbu['median'];

        }

        $zbbu = (($periksa_balita->berat_badan - $bbu['median'])/$nsbr);

        /**
         * Hitung TBU
         * ============================================================
         */    
        $tbu = TBU::where('jk', $periksa_balita->dataBalita->jenis_kelamin)->where('umur', $umur_bulat)->first();

        /**
         * cek apakah tinggi badan lebih kecil dari median
         */
        if ($periksa_balita->tinggi_badan < $tbu['median']) {
            
            $nsbr = $tbu['median'] - $tbu['sdmin1'];
            
        }else{

            $nsbr = $tbu['sdplus1'] - $tbu['median'];

        }

        $ztbu = (($periksa_balita->tinggi_badan - $tbu['median'])/$nsbr);

        /**
         * Hitung ZBBTB
         * =====================================================
         */
        /**
         * Cek umur balita apaka lebih kecil sama dengan 24 bulan
         */
        if ($umur_bulat <= 24) {

            /**
             * mengambil data dari table BBTB1
             */
            $bbtb_1 = BBTB1::where('jk', $periksa_balita->dataBalita->jenis_kelamin)->where('tb', $periksa_balita->tinggi_badan)->first();

            if ($periksa_balita->berat_badan < $bbtb_1['median']) {

                $nsbr = $bbtb_1['median'] - $bbtb_1['sdmin1'];

            }else{

                $nsbr = $bbtb_1['sdplus1'] - $bbtb_1['median'];

            }

            // return "oke";

            $zbbtb = (($periksa_balita->berat_badan - $bbtb_1['median'])/$nsbr);

        /**
         * Apabila umur balita diatas 24 bulan
         */
        }else {

            $bbtb_2 = BBTB2::where('jk', $periksa_balita->dataBalita->jenis_kelamin)->where('tb', $periksa_balita->tinggi_badan)->first();

            if ($periksa_balita->berat_badan < $bbtb_2['median']) {

                $nsbr = $bbtb_2['median'] - $bbtb_2['sdmin1'];

            }else{

                $nsbr = $bbtb_2['sdplus1'] - $bbtb_2['median'];

            }

            $zbbtb = (($periksa_balita->berat_badan - $bbtb_2['median'])/$nsbr);

        }

        /**
         * Insert Score
         */
        
        $input['month']     = $month;

        $input['id_balita'] = $periksa_balita->id_balita;

        $input['id_periksa']= $periksa_balita->id;

        $input['zbbu']      = $zbbu;

        $input['ztbu']      = $ztbu;

        $input['zbbtb']     = $zbbtb;

        Score::create($input);
    }

    // public function cobaEnergi()
    // {
    //     return $this->zbbuGizikurang();
    // }

    /**
     * ZBBU
     * =================================================
     */
    public function zbbuGiziburuk()
    {
        $score = Score::with('periksa')->where('id_periksa', 1)->with('dataBalita')->where('id_balita', 1)->first();

        if ($score->zbbu <= -3) {
            
            $gizi_buruk = 1;

        }else if($score->zbbu >= -3 && $score->zbbu <= -2) {

            $gizi_buruk = (-2 - $score->zbbu);

        }else if($score->zbbu >= -2) {

            $gizi_buruk = 0;

        }

        return $gizi_buruk;

    }


    public function zbbuGizikurang()
    {
        $score = Score::with('periksa')->where('id_periksa', 1)->with('dataBalita')->where('id_balita', 1)->first();

        if ($score->zbbu <= -3 || $score->zbbu >= 0) {
            
             $gizi_kurang = 0;

        }else if($score->zbbu >= -3 && $score->zbbu <= -2) {

             $gizi_kurang = $score->zbbu + 3;

        }else if($score->zbbu >= -2 && $score->zbbu <= 0) {

             $gizi_kurang = (-$score->zbbu)/2;

        }else if($score->zbbu == -2) {

             $gizi_kurang = 1;
        }
		return $gizi_kurang;

    }

    public function zbbuGizibaik()
    {
        $score = Score::with('periksa')->where('id_periksa', 1)->with('dataBalita')->where('id_balita', 1)->first();

        if ($score->zbbu <= -2 || $score->zbbu >= 2) {
            
            $gizi_baik = 0;

        }else if($score->zbbu >= -2 && $score->zbbu <= 0) {

            $gizi_baik = ($score->zbbu + 2)/2;

        }else if($score->zbbu >= 0 && $score->zbbu <= 2) {

            $gizi_baik = (2 - $score->zbbu)/2;

        }else if($score->zbbu == 0) {

            $gizi_baik = 1;
        }
        return $gizi_baik;

    }

    public function zbbuGizilebih()
    {
        $score = Score::with('periksa')->where('id_periksa', 1)->with('dataBalita')->where('id_balita', 1)->first();

        if ($score->zbbu >= 0 && $score->zbbu <= 2) {
            
            $gizi_lebih = ($score->zbbu)/2;

        }else if($score->zbbu >= 2) {

            $gizi_lebih = 1;

        }else if($score->zbbu <= 0) {

            $gizi_lebih = 0;
        }

        return $gizi_lebih;

    }

    /**
     * ZTBU
     * ============================================
     */

    public function ztbuSangatPendek()
    {
        $score = Score::with('periksa')->where('id_periksa', 1)->with('dataBalita')->where('id_balita', 1)->first();

        if ($score->ztbu <= -3) {
            
            return $sangat_pendek = 1;

        }else if($score->ztbu >= -3 && $score->ztbu <= -2) {

            return $sangat_pendek = (-2 - $score->ztbu);

        }else if($score->ztbu >= -2) {

            return $sangat_pendek = 0;

        }

    }


    public function ztbuPendek()
    {
        $score = Score::with('periksa')->where('id_periksa', 1)->with('dataBalita')->where('id_balita', 1)->first();

        if ($score->ztbu <= -3 || $score->ztbu >= 0) {
            
            return $pendek = 0;

        }else if($score->ztbu >= -3 && $score->ztbu <= -2) {

            return $pendek = $score->ztbu + 3;

        }else if($score->ztbu >= -2 && $score->ztbu <= 0) {

            return $pendek = (-$score->ztbu)/2;

        }else if($score->ztbu == -2) {

            return $pendek = 1;
        }

    }

    public function ztbuNormal()
    {
        $score = Score::with('periksa')->where('id_periksa', 1)->with('dataBalita')->where('id_balita', 1)->first();

        if ($score->ztbu <= -2 || $score->ztbu >= 2) {
            
            return $normal_ztbu = 0;

        }else if($score->ztbu >= -2 && $score->ztbu <= 0) {

            return $normal_ztbu = ($score->ztbu + 2)/2;

        }else if($score->ztbu >= 0 && $score->ztbu <= 2) {

            return $normal_ztbu = (2 - $score->ztbu)/2;

        }else if($score->ztbu == 0) {

            return $normal_ztbu = 1;
        }

    }

    public function ztbuTinggi()
    {
        $score = Score::with('periksa')->where('id_periksa', 1)->with('dataBalita')->where('id_balita', 1)->first();

        if ($score->ztbu >= 0 && $score->ztbu <= 2) {
            
            $tinggi = ($score->ztbu)/2;

        }else if($score->ztbu >= 2) {

            $tinggi = 1;

        }else if($score->ztbu <= 0) {

            $tinggi = 0;
        }

        return $tinggi;

    }


    /**
     * ZBBTB
     * ============================================
     */
    public function zbbtbSangatKurus()
    {
        $score = Score::with('periksa')->where('id_periksa', 1)->with('dataBalita')->where('id_balita', 1)->first();

        if ($score->zbbtb <= -3) {
            
            return $sangat_kurus = 1;

        }else if($score->zbbtb >= -3 && $score->zbbtb <= -2) {

            return $sangat_kurus = (-2 - $score->zbbtb);

        }else if($score->zbbtb >= -2) {

            return $sangat_kurus = 0;

        }

    }

    public function zbbtbKurus()
    {
        $score = Score::with('periksa')->where('id_periksa', 1)->with('dataBalita')->where('id_balita', 1)->first();

        if ($score->zbbtb <= -3 || $score->zbbtb >= 0) {
            
            return $kurus = 0;

        }else if($score->zbbtb >= -3 && $score->zbbtb <= -2) {

            return $kurus = $score->zbbtb + 3;

        }else if($score->zbbtb >= -2 && $score->zbbtb <= 0) {

            return $kurus = (-$score->zbbtb)/2;

        }else if($score->zbbtb == -2) {

            return $kurus = 1;
        }

    }

    public function zbbtbNormal()
    {
        $score = Score::with('periksa')->where('id_periksa', 1)->with('dataBalita')->where('id_balita', 1)->first();

        if ($score->zbbtb <= -2 || $score->zbbtb >= 2) {
            
            $normal_zbbtb = 0;

        }else if($score->zbbtb >= -2 && $score->zbbtb <= 0) {

            $normal_zbbtb = ($score->zbbtb + 2)/2;

        }else if($score->zbbtb >= 0 && $score->zbbtb <= 2) {

            $normal_zbbtb = (2 - $score->zbbtb)/2;

        }else if($score->zbbtb == 0) {

            $normal_zbbtb = 1;
        }

        return $normal_zbbtb;

    }

    public function zbbtbGemuk()
    {
        $score = Score::with('periksa')->where('id_periksa', 1)->with('dataBalita')->where('id_balita', 1)->first();

        if ($score->zbbtb >= 0 && $score->zbbtb <= 2) {
            
            return $gemuk = ($score->zbbtb)/2;

        }else if($score->zbbtb >= 2) {

            return $gemuk = 1;

        }else if($score->zbbtb <= 0) {

            return $gemuk = 0;
        }

        return $gemuk;

    }

    /**
     * Cari energi 
     */
    public function CariEnergi($id_periksa = 1, $id_balita = 1)
    {
        $score = Score::with('periksa')->where('id_periksa', $id_periksa)->with('dataBalita')->where('id_balita', $id_balita)->first();


        // return $score->periksa->berat_badan;
        
        $umur_tahun = (strtotime($score->periksa->tgl_periksa) - strtotime($score->dataBalita->tgl_lahir)) / (60 * 60 * 24 * 30 *12);
        /**
         * Pembulatan umur ke tahun
         */
        $umur_tahun_bulat = floor($umur_tahun);

        /**
         * CARI ENERGI
         */
        if ($umur_tahun_bulat <= 1) {
            
            $energi = 110 * $score->periksa->berat_badan;  

        } else if ($umur_tahun_bulat <= 3) {

            $energi = 100 * $score->periksa->berat_badan;

        } else if ($umur_tahun_bulat <= 5) {

            $energi = 90 * $score->periksa->berat_badan;
        }

        return $energi;
    }

    public function CariProteinDIIT($id_periksa = 1, $id_balita = 1)
    {
        $score = Score::with('periksa')->where('id_periksa', $id_periksa)->with('dataBalita')->where('id_balita', $id_balita)->first();

        $umur_bulan = (strtotime($score->periksa->tgl_periksa) - strtotime($score->dataBalita->tgl_lahir)) / (60 * 60 * 24 * 30);

        $umur_bulan_bulat = floor($umur_bulan);

        /**
         * Protein DIIT
         */
        if ($umur_bulan_bulat <= 48) {
            
            $protein_diit = 1.84 * $score->periksa->berat_badan;

        }else{

            $protein_diit =  1.79 * $score->periksa->berat_badan;
        }

        return $protein_diit;
    }

    public function CariProteinKKP($id_periksa = 1, $id_balita = 1)
    {
        $score = Score::with('periksa')->where('id_periksa', $id_periksa)->with('dataBalita')->where('id_balita', $id_balita)->first();

        $umur_bulan = (strtotime($score->periksa->tgl_periksa) - strtotime($score->dataBalita->tgl_lahir)) / (60 * 60 * 24 * 30);

        $umur_bulan_bulat = floor($umur_bulan);

        /**
         * Protein KKP
         */
        if ($umur_bulan_bulat <= 48) {
            
            $protein_kkp = 2.05 * $score->periksa->berat_badan;

        }else{

            $protein_kkp =  2.03 * $score->periksa->berat_badan;
        }

        return $protein_kkp;
    }

    /**
     * Rule 1
	 * R1 Zbbu(gizilebih) &Ztbu(Tinggi) & Zbbtb(Normal) z1= energi-(0.2*energi)  y1=diit
     * ============================================
     */	
    public function R_1($gizi_lebih = 0, $tinggi = 0, $normal_zbbtb = 0.95454545)
    {

        $data_energi = $this->CariEnergi();

        $data_protein_diit = $this->CariProteinDIIT();

        $data_protein_kkp = $this->CariProteinKKP();

        /**
         * cari nilai minimum R1
         */
        $r_1 = min($gizi_lebih, $tinggi, $normal_zbbtb);

        /**
         * Konsekuen z1 dan y1
         */
        $z1 = $data_energi - (0.2 * $data_energi);

        $y1 = $data_protein_diit;

        $rz1 = $r_1 * $z1;

        $ry1= $r_1 * $y1;

        return $data_R19 = array(
            'r_1' => $r_1,

            'ry1' => $ry1,

            'rz1' => $rz1
        );
        

    }

    /**
     * Rule 2
	 * R2 Zbbu(gizilebih) &Ztbu(Normal)  & Zbbtb(Gemuk) z2= energi-(0.1*energi) y2=diit
     * ============================================
     */
	
    public function R_2($gizi_lebih = 0, $normal_ztbu = 0.180555, $gemuk = 0.04545455)
    {
        $data_energi = $this->CariEnergi();

        $data_protein_diit = $this->CariProteinDIIT();

        $data_protein_kkp = $this->CariProteinKKP();

        /**
         * cari nilai minimum r2
         */
        $r_2 = min($gizi_lebih, $normal_ztbu, $gemuk);

        /**
         * Konsekuen z2 dan y2
         */
        $z2 = $data_energi - (0.1 * $data_energi);

        $y2 = $data_protein_diit;

        $rz2 = $r_2 * $z2;

        $ry2= $r_2 * $y2;

        return $data_R19 = array(
            'r_2' => $r_2,

            'ry2' => $ry2,

            'rz2' => $rz2
        );
    }

    /**
     * Rule 3
	 * R3 Zbbu(gizilebih) &Ztbu(Pendek) & Zbbtb(Gemuk) z3=energi-(0.1*energi) y3=diit
     * ============================================
     */

    public function R_3($gizi_lebih = 0, $pendek = 0.819445, $gemuk = 0.04545455)
    {
        $data_energi = $this->CariEnergi();

        $data_protein_diit = $this->CariProteinDIIT();

        $data_protein_kkp = $this->CariProteinKKP();

        /**
         * cari nilai minimum r3
         */
        $r_3 = min($gizi_lebih, $pendek, $gemuk);

        /**
         * Konsekuen z3 dan y3
         */
        $z3 = $data_energi - (0.1 * $data_energi);

        $y3 = $data_protein_diit;

        $rz3 = $r_3 * $z3;

        $ry3= $r_3 * $y3;

        return $data_R3 = array(
            'r_3' => $r_3,

            'ry3' => $ry3,

            'rz3' => $rz3
        );
        
    }
    /**
     * Rule 4
     * R4 Zbbu(gizilebih) &Ztbu(SangatPendek) & Zbbtb(Gemuk) z4=energi-(0.2*energi) y4=diit
     * ============================================
     */

    public function R_4($gizi_lebih = 0, $sangat_pendek = 0, $gemuk = 0.04545455)
    {
        $data_energi = $this->CariEnergi();

        $data_protein_diit = $this->CariProteinDIIT();

        $data_protein_kkp = $this->CariProteinKKP();

        /**
         * cari nilai minimum r4
         */
        $r_4 = min($gizi_lebih, $sangat_pendek, $gemuk);

        /**
         * Konsekuen z4 dan y4
         */
        $z4 = $data_energi - (0.2 * $data_energi);

        $y4 = $data_protein_diit;

        $rz4 = $r_4 * $z4;

        $ry4= $r_4 * $y4;

        return $data_R4 = array(
            'r_4' => $r_4,

            'ry4' => $ry4,

            'rz4' => $rz4
        );
    }

    /**
     * Rule 5
     * R5 Zbbu(gizibaik) &Ztbu(Tinggi) & Zbbtb(Normal) z5= energi y5=diit
     * ============================================
     */

    public function R_5($gizi_baik = 0.59375, $tinggi = 0, $normal_zbbtb = 0.95454545)
    {
        $data_energi = $this->CariEnergi();

        $data_protein_diit = $this->CariProteinDIIT();

        $data_protein_kkp = $this->CariProteinKKP();

        /**
         * cari nilai minimum r5
         */
        $r_5 = min($gizi_baik, $tinggi, $normal_zbbtb);

        /**
         * Konsekuen z5 dan y5
         */
        $z5 = $data_energi;

        $y5 = $data_protein_diit;

        $rz5 = $r_5 * $z5;

        $ry5= $r_5 * $y5;

        return $data_R5 = array(
            'r_5' => $r_5,

            'ry5' => $ry5,

            'rz5' => $rz5
        );
       
    }  

    /**
     * Rule 6
     * R6 min( Zbbu(gizibaik) &Ztbu(Normal) & Zbbtb(Normal) z6= energi y6=diit
     * ============================================
     */

    public function R_6($gizi_baik = 0.59375, $normal_ztbu = 0.180555, $normal_zbbtb = 0.95454545)
    {
        $data_energi = $this->CariEnergi();

        $data_protein_diit = $this->CariProteinDIIT();

        $data_protein_kkp = $this->CariProteinKKP();

        /**
         * cari nilai minimum r6
         */
        $r_6 = min($gizi_baik, $normal_ztbu, $normal_zbbtb);

        /**
         * Konsekuen z6 dan y6
         */
        $z6 = $data_energi;

        $y6 = $data_protein_diit;

        $rz6 = $r_6 * $z6;

        $ry6= $r_6 * $y6;

        return $data_R6 = array(
            'r_6' => $r_6,

            'ry6' => $ry6,

            'rz6' => $rz6
        );

    }

    /**
     * Rule 7
     * R7 min( Zbbu(gizibaik) &Ztbu(Pendek) & Zbbtb(Normal) z7= energi y7=diit
     * ============================================
     */

    public function R_7($gizi_baik = 0.59375, $pendek = 0.819445, $normal_zbbtb = 0.95454545)
    {
        $data_energi = $this->CariEnergi();

        $data_protein_diit = $this->CariProteinDIIT();

        $data_protein_kkp = $this->CariProteinKKP();

        /**
         * cari nilai minimum r7
         */
        $r_7 = min($gizi_baik, $pendek, $normal_zbbtb);

        /**
         * Konsekuen z7 dan y7
         */
        $z7 = $data_energi;

        $y7 = $data_protein_diit;

        $rz7 = $r_7 * $z7;

        $ry7= $r_7 * $y7;

        return $data_R7 = array(
            'r_7' => $r_7,

            'ry7' => $ry7,

            'rz7' => $rz7
        );
        
    }

    /**
     * Rule 8
     * R8 min( Zbbu(gizibaik) &Ztbu(SangatPendek) & Zbbtb(Gemuk) z8= energi y8=diit
     * ============================================
     */

    public function R_8($gizi_baik = 0.59375, $sangat_pendek = 0, $gemuk = 0.04545455)
    {
        $data_energi = $this->CariEnergi();

        $data_protein_diit = $this->CariProteinDIIT();

        $data_protein_kkp = $this->CariProteinKKP();

        /**
         * cari nilai minimum r8
         */
        $r_8 = min($gizi_baik, $sangat_pendek, $gemuk);

        /**
         * Konsekuen z8 dan y8
         */
        $z8 = $data_energi;

        $y8 = $data_protein_diit;

        $rz8 = $r_8 * $z8;

        $ry8= $r_8 * $y8;

        return $data_R8 = array(
            'r_8' => $r_8,

            'ry8' => $ry8,

            'rz8' => $rz8
        );
 
    }

    /**
     * Rule 9
     * R9 min( Zbbu(gizibaik) &Ztbu(SangatPendek) & Zbbtb(Normal) z9= energi y9=diit
     * ============================================
     */

    public function R_9($gizi_baik = 0.59375, $sangat_pendek = 0, $normal_zbbtb = 0.95454545)
    {
        $data_energi = $this->CariEnergi();

        $data_protein_diit = $this->CariProteinDIIT();

        $data_protein_kkp = $this->CariProteinKKP();

        /**
         * cari nilai minimum r9
         */
        $r_9 = min($gizi_baik, $sangat_pendek, $normal_zbbtb);

        /**
         * Konsekuen z9 dan y9
         */
        $z9 = $data_energi;

        $y9 = $data_protein_diit;

        $rz9 = $r_9 * $z9;

        $ry9= $r_9 * $y9;

        return $data_R9 = array(
            'r_9' => $r_9,

            'ry9' => $ry9,

            'rz9' => $rz9
        );
        
    }

    /**
     * Rule 10
     * R10 min( Zbbu(gizikurang) &Ztbu(Tinggi) & Zbbtb(Sangat Kurus) z10 = energi+(0.4*energi) y10=diit
     * ============================================
     */

    public function R_10($gizi_kurang = 0.40625, $tinggi = 0, $sangat_kurus = 0)
    {
        $data_energi = $this->CariEnergi();

        $data_protein_diit = $this->CariProteinDIIT();

        $data_protein_kkp = $this->CariProteinKKP();

        /**
         * cari nilai minimum r10
         */
        $r_10 = min($gizi_kurang, $tinggi, $sangat_kurus);

        /**
         * Konsekuen z10 dan y10
         */
        $z10 = $data_energi + (0.4 * $data_energi);

        $y10 = $data_protein_diit;

        $rz10 = $r_10 * $z10;

        $ry10= $r_10 * $y10;

        return $data_R10 = array(
            'r_10' => $r_10,

            'ry10' => $ry10,

            'rz10' => $rz10
        );

    }

    /**
     * Rule 11
     * R11 min( Zbbu(gizikurang) &Ztbu(Normal) & Zbbtb(Kurus) z11= energi+(0.2*energi) y11=diit
     * ============================================
     */

    public function R_11($gizi_kurang = 0.40625, $normal_ztbu = 0.180555, $kurus = 0)
    {
        $data_energi = $this->CariEnergi();

        $data_protein_diit = $this->CariProteinDIIT();

        $data_protein_kkp = $this->CariProteinKKP();

        /**
         * cari nilai minimum r11
         */
        $r_11 = min($gizi_kurang, $normal_ztbu, $kurus);

        /**
         * Konsekuen z11 dan y11
         */
        $z11 = $data_energi + (0.2 * $data_energi);

        $y11 = $data_protein_diit;

        $rz11 = $r_11 * $z11;

        $ry11= $r_11 * $y11;

        return $data_R11 = array(
            'r_11' => $r_11,

            'ry11' => $ry11,

            'rz11' => $rz11
        );
        
    }

    /**
     * Rule 12
     * R12 min( Zbbu(gizikurang) &Ztbu(Pendek) & Zbbtb(Normal) z12 = energi+(0.2*energi) y12=diit
     * ============================================
     */

    public function R_12($gizi_kurang = 0.40625, $pendek = 0.819445, $normal_zbbtb = 0.95454545)
    {
        $data_energi = $this->CariEnergi();

        $data_protein_diit = $this->CariProteinDIIT();

        $data_protein_kkp = $this->CariProteinKKP();

        /**
         * cari nilai minimum r12
         */
        $r_12 = min($gizi_kurang, $pendek, $normal_zbbtb);

        /**
         * Konsekuen z12 dan y12
         */
        $z12 = $data_energi + (0.2 * $data_energi);

        $y12 = $data_protein_diit;

        $rz12 = $r_12 * $z12;

        $ry12= $r_12 * $y12;

        return $data_R12 = array(
            'r_12' => $r_12,

            'ry12' => $ry12,

            'rz12' => $rz12
        );

    }

    /**
     * Rule 13
     * R13 min( Zbbu(gizikurang) &Ztbu(Pendek) & Zbbtb(Kurus) z13= energi+(0.3*energi) y13=diit
     * ============================================
     */

    public function R_13($gizi_kurang = 0.40625, $pendek = 0.819445, $kurus = 0)
    {
        $data_energi = $this->CariEnergi();

        $data_protein_diit = $this->CariProteinDIIT();

        $data_protein_kkp = $this->CariProteinKKP();

        /**
         * cari nilai minimum r13
         */
        $r_13 = min($gizi_kurang, $pendek, $kurus);

        /**
         * Konsekuen z13 dan y13
         */
        $z13 = $data_energi + (0.3 * $data_energi);

        $y13 = $data_protein_diit;

        $rz13 = $r_13 * $z13;

        $ry13= $r_13 * $y13;

        return $data_R13 = array(
            'r_13' => $r_13,

            'ry13' => $ry13,

            'rz13' => $rz13
        );
        
    }

    /**
     * Rule 14
     * R14 min( Zbbu(gizikurang) &Ztbu(SangatPendek) & Zbbtb(Normal) z14 =energi+(0.3*energi) y14=diit
     * ============================================
     */

    public function R_14($gizi_kurang = 0.40625, $sangat_pendek = 0, $normal_zbbtb = 0.95454545)
    {
        $data_energi = $this->CariEnergi();

        $data_protein_diit = $this->CariProteinDIIT();

        $data_protein_kkp = $this->CariProteinKKP();

        /**
         * cari nilai minimum r14
         */
        $r_14 = min($gizi_kurang, $sangat_pendek, $normal_zbbtb);

        /**
         * Konsekuen z14 dan y14
         */
        $z14 = $data_energi + (0.3 * $data_energi);

        $y14 = $data_protein_diit;

        $rz14 = $r_14 * $z14;

        $ry14= $r_14 * $y14;

        return $data_R14 = array(
            'r_14' => $r_14,

            'ry14' => $ry14,

            'rz14' => $rz14
        );
        
    }

    /**
     * Rule 15
     * R15 min( Zbbu(giziburuk) &Ztbu(Tinggi) & Zbbtb(SangatKurus) z15= energi+(0.4*energi) y15=kkp
     * ============================================
     */

    public function R_15($gizi_buruk = 0, $tinggi = 0, $sangat_kurus = 0)
    {
        $data_energi = $this->CariEnergi();

        $data_protein_diit = $this->CariProteinDIIT();

        $data_protein_kkp = $this->CariProteinKKP();

        /**
         * cari nilai minimum r15
         */
        $r_15 = min($gizi_buruk, $tinggi, $sangat_kurus);

        /**
         * Konsekuen z15 dan y15
         */
        $z15 = $data_energi + (0.4 * $data_energi);

        $y15 = $data_protein_kkp;

        $rz15 = $r_15 * $z15;

        $ry15= $r_15 * $y15;

        return $data_R15 = array(
            'r_15' => $r_15,

            'ry15' => $ry15,

            'rz15' => $rz15
        );

    }

    /**
     * Rule 16
     * R16 min( Zbbu(giziburuk) &Ztbu(Normal) & Zbbtb(Kurus) z16 = energi+(0.3*energi) y16=kkp
     * ============================================
     */

    public function R_16($gizi_buruk = 0, $normal_ztbu = 0.180555, $kurus = 0)
    {
        $data_energi = $this->CariEnergi();

        $data_protein_diit = $this->CariProteinDIIT();

        $data_protein_kkp = $this->CariProteinKKP();

        /**
         * cari nilai minimum r16
         */
        $r_16 = min($gizi_buruk, $normal_ztbu, $kurus);

        /**
         * Konsekuen z16 dan y16
         */
        $z16 = $data_energi + (0.3 * $data_energi);

        $y16 = $data_protein_kkp;

        $rz16 = $r_16 * $z16;

        $ry16= $r_16 * $y16;

        return $data_R16 = array(
            'r_16' => $r_16,

            'ry16' => $ry16,

            'rz16' => $rz16
        );
    }

    /**
     * Rule 17
     * R17 min( Zbbu(giziburuk) &Ztbu(Pendek) & Zbbtb(Kurus) z17= energi+(0.4*energi) y17=kkp
     * ============================================
     */

    public function R_17($gizi_buruk = 0, $pendek = 0.819445, $kurus = 0)
    {
        $data_energi = $this->CariEnergi();

        $data_protein_diit = $this->CariProteinDIIT();

        $data_protein_kkp = $this->CariProteinKKP();

        /**
         * cari nilai minimum r17
         */
        $r_17 = min($gizi_buruk, $pendek, $kurus);

        /**
         * Konsekuen z17 dan y17
         */
        $z17 = $data_energi + (0.4 * $data_energi);

        $y17 = $data_protein_kkp;

        $rz17 = $r_17 * $z17;

        $ry17= $r_17 * $y17;

        return $data_R17 = array(
            'r_17' => $r_17,

            'ry17' => $ry17,

            'rz17' => $rz17
        );
        
    }

    /**
     * Rule 18
     * R18 min( Zbbu(giziburuk) &Ztbu(SangatPendek) & Zbbtb(Normal) z18= energi+(0.3*energi) y18=kkp
     * ============================================
     */

    public function R_18($gizi_buruk = 0, $sangat_pendek = 0, $normal_zbbtb = 0.95454545)
    {
        $data_energi = $this->CariEnergi();

        $data_protein_diit = $this->CariProteinDIIT();

        $data_protein_kkp = $this->CariProteinKKP();

        /**
         * cari nilai minimum r18
         */
        $r_18 = min($gizi_buruk, $sangat_pendek, $normal_zbbtb);

        /**
         * Konsekuen z18 dan y18
         */
        $z18 = $data_energi + (0.3 * $data_energi);

        $y18 = $data_protein_kkp;

        $rz18 = $r_18 * $z18;

        $ry18= $r_18 * $y18;

        return $data_R18 = array(
            'r_18' => $r_18,

            'ry18' => $ry18,

            'rz18' => $rz18
        );

    }

   /**
     * Rule 19
     * R19 min( Zbbu(giziburuk) &Ztbu(SangatPendek) & Zbbtb(Kurus) z19= energi+(0.4*energi) y19=kkp
     * ============================================
     */

    public function R_19($gizi_buruk = 0, $sangat_pendek = 0, $kurus = 0)
    {
        $data_energi = $this->CariEnergi();

        $data_protein_diit = $this->CariProteinDIIT();

        $data_protein_kkp = $this->CariProteinKKP();

        /**
         * cari nilai minimum r19
         */
        $r_19 = min($gizi_buruk, $sangat_pendek, $kurus);

        /**
         * Konsekuen z19 dan y19
         */
        $z19 = $data_energi + (0.4 * $data_energi);

        $y19 = $data_protein_kkp;

        $rz19 = $r_19 * $z19;

        $ry19 = $r_19 * $y19;

        return $data_R19 = array(
            'r_19' => $r_19,

            'ry19' => $ry19,

            'rz19' => $rz19
        );
    }

    public function sum_R()
    {
        $data_1     = $this->R_1();
        $data_2     = $this->R_2();
        $data_3     = $this->R_3();
        $data_4     = $this->R_4();
        $data_5     = $this->R_5();
        $data_6     = $this->R_6();
        $data_7     = $this->R_7();
        $data_8     = $this->R_8();
        $data_9     = $this->R_9();
        $data_10    = $this->R_10();
        $data_11    = $this->R_11();
        $data_12    = $this->R_12();
        $data_13    = $this->R_13();
        $data_14    = $this->R_14();
        $data_15    = $this->R_15();
        $data_16    = $this->R_16();
        $data_17    = $this->R_17();
        $data_18    = $this->R_18();
        $data_19    = $this->R_19();

        /**
         * Penjumlahan R
         */
        $jumlah_R = ($data_1['r_1'] + $data_2['r_2'] + $data_3['r_3'] + $data_4['r_4'] + $data_5['r_5'] + $data_6['r_6'] +$data_7['r_7'] + $data_8['r_8'] + $data_9['r_9'] +$data_10['r_10'] + $data_11['r_11'] + $data_12['r_12'] + $data_13['r_13'] + $data_14['r_14'] + $data_15['r_15'] + $data_16['r_16'] + $data_17['r_17'] + $data_18['r_18'] + $data_19['r_19']);

        /**
         * Penjumlahan RZ
         */
        $jumlah_RZ = ($data_1['rz1'] + $data_2['rz2'] + $data_3['rz3'] + $data_4['rz4'] + $data_5['rz5'] + $data_6['rz6'] +$data_7['rz7'] + $data_8['rz8'] + $data_9['rz9'] +$data_10['rz10'] + $data_11['rz11'] + $data_12['rz12'] + $data_13['rz13'] + $data_14['rz14'] + $data_15['rz15'] + $data_16['rz16'] + $data_17['rz17'] + $data_18['rz18'] + $data_19['rz19']);

        /**
        * Penjumlahan RY
         */
        $jumlah_RY = ($data_1['ry1'] + $data_2['ry2'] + $data_3['ry3'] + $data_4['ry4'] + $data_5['ry5'] + $data_6['ry6'] +$data_7['ry7'] + $data_8['ry8'] + $data_9['ry9'] +$data_10['ry10'] + $data_11['ry11'] + $data_12['ry12'] + $data_13['ry13'] + $data_14['ry14'] + $data_15['ry15'] + $data_16['ry16'] + $data_17['ry17'] + $data_18['ry18'] + $data_19['ry19']);
        /**
        * Hasil akhir Energi
         */
        $hasil_energi = $jumlah_RZ / $jumlah_R;
        /**
        * Hasil akhir Protein
         */
        return $hasil_protein = $jumlah_RY / $jumlah_R;

    }	
}
