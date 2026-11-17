<?php

declare(strict_types=1);
// Bubble sort: O(n²) waktu terburuk dan O(1) ruang tambahan pada array lokal.
function urutkan(array $angka): array
{
    $n = count($angka);
    for ($i = 0; $i < $n - 1; $i++) {
        for ($j = 0; $j < $n - $i - 1; $j++) {
            if ($angka[$j] > $angka[$j + 1]) {
                [$angka[$j], $angka[$j + 1]] = [$angka[$j + 1], $angka[$j]];
            }
        }
    }
    return $angka;
}
// Pencarian linear: O(n) waktu dan O(1) ruang tambahan.
function cari(array $angka, int $target): int
{
    foreach ($angka as $i => $nilai) {
        if ($nilai === $target) {
            return $i;
        }
    }
    return -1;
}
$data = urutkan([5, 2, 9, 1]);
echo implode(", ", $data) . PHP_EOL;
echo "Posisi 5: " . cari($data, 5) . PHP_EOL;
