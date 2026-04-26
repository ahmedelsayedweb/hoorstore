#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Decodes garbled (mojibake) CSV files - Arabic UTF-8 displayed as Latin-1
Usage: python3 decode_csv.py input.csv output.csv
"""
import sys
import os

def decode_mojibake(text):
    try:
        return text.encode('latin-1').decode('utf-8')
    except (UnicodeEncodeError, UnicodeDecodeError):
        return text

def decode_file(input_path, output_path):
    with open(input_path, 'r', encoding='utf-8') as f:
        content = f.read()
    decoded = decode_mojibake(content)
    with open(output_path, 'w', encoding='utf-8') as f:
        f.write(decoded)
    print(f"Decoded: {input_path} -> {output_path}")

if __name__ == '__main__':
    if len(sys.argv) < 3:
        print("Usage: python3 decode_csv.py input.csv output.csv")
        sys.exit(1)
    decode_file(sys.argv[1], sys.argv[2])
