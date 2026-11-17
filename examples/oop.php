<?php

declare(strict_types=1);
namespace Contoh;

interface Pesan
{
    public function tampil(): string;
}
abstract class Pemberitahuan implements Pesan
{
    public function __construct(protected string $isi) {}
}
final class Informasi extends Pemberitahuan
{
    public function tampil(): string
    {
        return "Informasi: " . $this->isi;
    }
}
final class Peringatan extends Pemberitahuan
{
    public function tampil(): string
    {
        return "Peringatan: " . $this->isi;
    }
}
/** @param list<Pesan> $daftar */
function tampilkan(array $daftar): void
{
    foreach ($daftar as $pesan) {
        echo $pesan->tampil() . PHP_EOL;
    }
}
tampilkan([new Informasi("Tugas dibuat"), new Peringatan("Tenggat dekat")]);
