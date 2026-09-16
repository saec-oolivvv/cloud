#!/usr/bin/env python3
"""Generate a Windows .ico file from icon.png with multiple sizes."""
import sys
import os
from PIL import Image

input_path = 'icons/icon.png'
output_path = 'icons/icon.ico'

if not os.path.exists(input_path):
    print(f'Input PNG not found: {input_path}')
    sys.exit(1)

img = Image.open(input_path)

sizes = [(16, 16), (24, 24), (32, 32), (48, 48), (64, 64), (128, 128), (256, 256)]
images = []
for size in sizes:
    resized = img.resize(size, Image.Resampling.LANCZOS)
    images.append(resized)

images[0].save(
    output_path,
    format='ICO',
    sizes=[img.size for img in images]
)

print(f'ICO created successfully: {output_path}')
print(f'File size: {os.path.getsize(output_path)} bytes')