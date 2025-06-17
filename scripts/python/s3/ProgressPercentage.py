import os
import sys
import threading

class ProgressPercentage(object):
    def __init__(self, file_name):
        self._progression = 0
        self._file_name = file_name
        self._lock = threading.Lock()
        self._size = float(os.path.getsize(file_name))

    def __call__(self, byte_amount):
        with self._lock:
            self._progression += byte_amount
            percentage = (self._progression / self._size) * 100
            sys.stdout.write(f"\rUploading {self._file_name} ({percentage:.2f}%)\n")
            sys.stdout.flush()

