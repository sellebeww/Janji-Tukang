<?php

use App\Models\Termin;
use App\Models\TerminConfirmation;
use App\Models\TerminPhoto;
use App\Models\User;
use App\Services\ConfirmationLink;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

require_once __DIR__.'/../Fase2/Helpers.php';

beforeEach(fn () => Storage::fake('local'));

/** Kesepakatan aktif (kedua pihak setuju). Mengembalikan [pemilik, agreement, termin 1]. */
function f3Aktif(): array
{
    $owner = User::factory()->create();
    [$a, $v] = f2Submitted($owner);
    test()->post(f2Url($v->id), ['decision' => 'setuju'])->assertOk();

    return [$owner, $a->fresh(), $a->termins()->first()];
}

function f3Jpeg(int $w = 40, int $h = 20, ?string $exif = null): string
{
    $im = imagecreatetruecolor($w, $h);
    imagefill($im, 0, 0, imagecolorallocate($im, 200, 80, 40));
    ob_start();
    imagejpeg($im);
    $jpeg = ob_get_clean();

    return $exif ? substr($jpeg, 0, 2).$exif.substr($jpeg, 2) : $jpeg;
}

/** Segmen APP1 EXIF minimal: Orientation + GPS (6°12'30" S). Dirakit tangan agar tes tidak butuh alat luar. */
function f3Exif(int $orientation): string
{
    $ifd0 = pack('v', 2).pack('vvVv', 0x0112, 3, 1, $orientation)."\0\0".pack('vvVV', 0x8825, 4, 1, 38).pack('V', 0);
    $gps = pack('v', 2).pack('vvV', 1, 2, 2)."S\0\0\0".pack('vvVV', 2, 5, 3, 68).pack('V', 0);
    $tiff = 'II'.pack('v', 42).pack('V', 8).$ifd0.$gps.pack('V6', 6, 1, 12, 1, 30, 1);

    return "\xFF\xE1".pack('n', 8 + strlen($tiff))."Exif\0\0".$tiff;
}

function f3ReadExif(string $bytes): array|false
{
    $s = fopen('php://temp', 'r+');
    fwrite($s, $bytes);
    rewind($s);

    return @exif_read_data($s);
}

/** File sungguhan di disk (bukan UploadedFile::fake, yang menebak mime dari ekstensi). */
function f3Upload(string $bytes, string $name = 'foto.jpg'): UploadedFile
{
    $path = tempnam(sys_get_temp_dir(), 'jt');
    file_put_contents($path, $bytes);

    return new UploadedFile($path, $name, null, null, true);
}

function f3Post(User $u, Termin $t, string $kind, string $bytes, string $name = 'foto.jpg')
{
    return test()->actingAs($u)->post(route('termin.foto.simpan', $t), ['jenis' => $kind, 'foto' => f3Upload($bytes, $name)]);
}

it('membuat termin hidup dari versi berlaku saat kedua pihak setuju', function () {
    [, $a, $t] = f3Aktif();

    expect($a->termins)->toHaveCount(2)
        ->and($a->termins->sum('amount'))->toBe($a->total_amount)
        ->and($t->status)->toBe('menunggu')
        ->and($t->agreement_version_id)->toBe($a->effectiveVersion()->id);
});

it('menghapus EXIF GPS, menerapkan orientasi, dan mengecilkan foto', function () {
    [$owner, , $t] = f3Aktif();
    $src = f3Jpeg(40, 20, f3Exif(6));
    expect(f3ReadExif($src))->toHaveKey('GPSLatitude'); // prasyarat: fixture memang berisi GPS

    f3Post($owner, $t, 'sebelum', $src)->assertRedirect(route('termin.show', $t))->assertSessionHasNoErrors();

    $photo = TerminPhoto::sole();
    $stored = Storage::disk('local')->get($photo->path);
    $exif = f3ReadExif($stored);
    expect($exif === false || ! array_key_exists('GPSLatitude', $exif))->toBeTrue()
        ->and(str_contains($stored, 'Exif'))->toBeFalse()
        ->and(array_slice(getimagesizefromstring($stored), 0, 2))->toBe([20, 40]) // orientasi 6 diterapkan ke piksel
        ->and($photo->sha256)->toBe(hash('sha256', $stored))
        ->and($photo->created_at->diffInSeconds(now(), true))->toBeLessThan(5);

    f3Post($owner, $t, 'sesudah', f3Jpeg(3000, 1000));
    $big = Storage::disk('local')->get(TerminPhoto::where('kind', 'sesudah')->sole()->path);
    expect(getimagesizefromstring($big)[0])->toBe(1600);
});

it('menolak file yang bukan gambar', function (string $bytes, string $name) {
    [$owner, , $t] = f3Aktif();

    f3Post($owner, $t, 'sebelum', $bytes, $name)->assertSessionHasErrors('foto');

    expect(TerminPhoto::count())->toBe(0)->and(Storage::disk('local')->allFiles())->toBe([]);
})->with([
    'teks bernama .jpg' => ['halo ini bukan gambar', 'foto.jpg'],
    'skrip PHP' => ['<?php echo 1;', 'x.php.jpg'],
    'GIF asli (tipe tidak diizinkan)' => ["GIF89a\x01\x00\x01\x00\x80\x00\x00\x00\x00\x00\xff\xff\xff!\xf9\x04\x01\x00\x00\x00\x00,\x00\x00\x00\x00\x01\x00\x01\x00\x00\x02\x02D\x01\x00;", 'foto.jpg'],
    'header JPEG tapi isi rusak' => ["\xFF\xD8\xFF\xE0".str_repeat("\x01", 64), 'foto.jpg'],
]);

it('menolak penutupan termin tanpa foto sebelum dan sesudah', function () {
    [$owner, $a, $t] = f3Aktif();

    // tanpa foto
    $this->actingAs($owner)->post(route('termin.konfirmasi', $t))->assertSessionHasErrors('termin');
    $this->actingAs($owner)->post(route('termin.tautan', $t))->assertSessionHasErrors('termin');

    // hanya foto sebelum
    f3Post($owner, $t, 'sebelum', f3Jpeg());
    $this->actingAs($owner)->post(route('termin.konfirmasi', $t))->assertSessionHasErrors('termin');

    // tautan yang diterbitkan paksa pun tidak bisa menutup termin
    $url = app(ConfirmationLink::class)->issue(ConfirmationLink::KONFIRMASI_TERMIN, $t->id);
    $this->get($url)->assertStatus(410);
    $this->post($url, ['decision' => 'setuju'])->assertStatus(410);

    expect(TerminConfirmation::count())->toBe(0)->and($t->fresh()->status)->toBe('menunggu');
});

it('menutup termin: foto lengkap, foto terkunci, lalu konfirmasi kedua pihak lewat tautan', function () {
    [$owner, $a, $t] = f3Aktif();

    f3Post($owner, $t, 'sebelum', f3Jpeg());
    expect($t->fresh()->status)->toBe('menunggu');
    f3Post($owner, $t, 'sesudah', f3Jpeg());
    expect($t->fresh()->status)->toBe('bukti_lengkap');

    // hapus foto sebelum konfirmasi -> kembali menunggu; unggah lagi
    $after = TerminPhoto::where('kind', 'sesudah')->sole();
    $this->actingAs($owner)->delete(route('foto.hapus', $after))->assertSessionHasNoErrors();
    expect($t->fresh()->status)->toBe('menunggu')->and(Storage::disk('local')->exists($after->path))->toBeFalse();
    f3Post($owner, $t, 'sesudah', f3Jpeg());

    // pembuat menutup; foto terkunci
    $this->actingAs($owner)->post(route('termin.konfirmasi', $t))->assertSessionHasNoErrors();
    expect($t->fresh()->status)->toBe('bukti_lengkap');
    f3Post($owner, $t, 'sebelum', f3Jpeg())->assertSessionHasErrors('foto');
    $this->actingAs($owner)->delete(route('foto.hapus', TerminPhoto::first()))->assertSessionHasErrors('foto');
    $this->actingAs($owner)->post(route('termin.konfirmasi', $t))->assertSessionHasErrors('termin'); // dua kali

    // pihak lawan lewat tautan
    $this->actingAs($owner)->post(route('termin.tautan', $t))->assertSessionHas('tautan');
    $url = session('tautan');
    auth()->logout();
    $page = $this->get($url)->assertOk()->assertSee('Pekerjaan sudah sesuai')->assertHeader('Cache-Control', 'no-store, private');
    preg_match_all('/src="([^"]*\/foto\/[^"]*)"/', $page->getContent(), $m);
    expect($m[1])->toHaveCount(2);
    $this->get(html_entity_decode($m[1][0]))->assertOk()->assertHeader('Content-Type', 'image/jpeg');

    $this->post($url, ['decision' => 'setuju'])->assertOk()->assertSee('sudah dicatat');
    expect($t->fresh()->status)->toBe('selesai')->and(TerminConfirmation::pluck('party')->sort()->values()->all())->toBe(['pemilik', 'tukang']);

    $this->post($url, ['decision' => 'setuju'])->assertStatus(410); // sekali pakai
    $this->get(html_entity_decode($m[1][0]))->assertNotFound(); // token terpakai, foto tak lagi terbuka
});

it('token termin A tidak membuka foto termin B', function () {
    [$owner, , $t] = f3Aktif();
    $other = Termin::where('id', '!=', $t->id)->first();
    f3Post($owner, $other, 'sebelum', f3Jpeg());
    $url = app(ConfirmationLink::class)->issue(ConfirmationLink::KONFIRMASI_TERMIN, $t->id);
    $token = basename(parse_url($url, PHP_URL_PATH));

    $signed = URL::temporarySignedRoute('konfirmasi.foto', now()->addHour(), ['token' => $token, 'photo' => TerminPhoto::sole()->ulid]);
    $this->get($signed)->assertNotFound();
    $this->get(preg_replace('/signature=[a-f0-9]+/', 'signature=deadbeef', $signed))->assertForbidden();
});

it('foto dan termin milik pengguna lain tidak terakses', function () {
    [$owner, $a, $t] = f3Aktif();
    f3Post($owner, $t, 'sebelum', f3Jpeg());
    $photo = TerminPhoto::sole();
    $intruder = User::factory()->create();

    $this->actingAs($owner)->get(route('foto.tampil', $photo))->assertOk()->assertHeader('Content-Type', 'image/jpeg');

    $this->actingAs($intruder);
    $this->get(route('foto.tampil', $photo))->assertNotFound();
    $this->delete(route('foto.hapus', $photo))->assertNotFound();
    $this->get(route('termin.show', $t))->assertNotFound();
    $this->get(route('agreements.timeline', $a))->assertNotFound();
    $this->post(route('termin.foto.simpan', $t), ['jenis' => 'sesudah', 'foto' => f3Upload(f3Jpeg())])->assertNotFound();
    $this->post(route('termin.konfirmasi', $t))->assertNotFound();
    $this->post(route('termin.tautan', $t))->assertNotFound();

    auth()->logout();
    $this->get(route('foto.tampil', $photo))->assertRedirect(route('login'));
    expect(Storage::disk('local')->exists($photo->path))->toBeTrue()->and(TerminPhoto::count())->toBe(1);
});

it('endpoint timeline memberi data termin tanpa path penyimpanan', function () {
    [$owner, $a, $t] = f3Aktif();
    f3Post($owner, $t, 'sebelum', f3Jpeg());

    $res = $this->actingAs($owner)->getJson(route('agreements.timeline', $a))->assertOk();
    $first = $res->json('termins.0');

    expect($res->json('termins'))->toHaveCount(2)
        ->and($first)->toMatchArray(['number' => 1, 'title' => 'Uang muka', 'amount_label' => 'Rp 500.000', 'status' => 'menunggu'])
        ->and($first['photos'])->toHaveCount(1)
        ->and($res->getContent())->not->toContain('termin/'.$t->ulid);

    $this->actingAs($owner)->get(route('termin.show', $t))->assertOk()->assertSee('Foto sebelum')->assertSee('accept="image/jpeg,image/png,image/webp"', false);
    $this->actingAs($owner)->get(route('agreements.show', $a))->assertOk()->assertSee('id="timeline"', false)->assertSee('Uang muka'); // fallback Blade
});

it('kesepakatan menjadi selesai hanya setelah semua termin selesai', function () {
    [, $a] = f3Aktif();
    [$t1, $t2] = $a->termins()->get()->all();
    $flow = app(App\Services\TerminFlow::class);
    foreach ([$t1, $t2] as $i => $t) {
        $t->update(['status' => 'bukti_lengkap']);
        $flow->confirm($t, 'pemilik', null, null);
        $flow->confirm($t, 'tukang', null, null);
        expect($a->fresh()->status)->toBe($i === 0 ? 'aktif' : 'selesai');
    }
});
