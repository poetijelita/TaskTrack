#!/usr/bin/env python3
"""Pengukuran sekuensial halaman login lokal TaskTrack, tanpa mengubah data."""
import statistics
import time
from urllib.request import urlopen

URL = 'http://127.0.0.1:8080/'
COUNT = 30
results = []
for _ in range(COUNT):
    start = time.perf_counter()
    with urlopen(URL, timeout=5) as response:
        response.read()
        if response.status != 200:
            raise RuntimeError(f'HTTP {response.status}')
    results.append((time.perf_counter() - start) * 1000)
print(f'{COUNT} permintaan sekuensial; HTTP 200')
print(f'min={min(results):.2f} ms, median={statistics.median(results):.2f} ms, max={max(results):.2f} ms')
