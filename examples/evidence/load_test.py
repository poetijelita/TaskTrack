#!/usr/bin/env python3
"""Beban ringan terkontrol: 50 GET lokal dengan maksimal 5 pekerja."""
from concurrent.futures import ThreadPoolExecutor
import statistics
import time
from urllib.request import urlopen

URL = 'http://127.0.0.1:8080/'

def request(_):
    start = time.perf_counter()
    with urlopen(URL, timeout=10) as response:
        response.read()
        status = response.status
    return status, (time.perf_counter() - start) * 1000

with ThreadPoolExecutor(max_workers=5) as pool:
    results = list(pool.map(request, range(50)))
times = [time for _, time in results]
print(f"jumlah={len(results)}; pekerja=5; http_200={sum(s == 200 for s, _ in results)}")
print(f"min={min(times):.2f} ms; median={statistics.median(times):.2f} ms; max={max(times):.2f} ms")
