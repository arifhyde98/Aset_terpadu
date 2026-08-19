import os
import glob
import re

files = glob.glob('/home/arif/Projek/SIPAT_Terpadu/resources/views/sipat/*/*.blade.php')

for filepath in files:
    with open(filepath, 'r') as f:
        content = f.read()

    # Convert layouts
    content = content.replace("<?= $this->extend('layouts/main') ?>", "@extends('layouts.app')")
    content = content.replace("<?= $this->section('content') ?>", "@section('content')")
    content = content.replace("<?= $this->endSection() ?>", "@endsection")

    # Convert foreach and if
    content = re.sub(r'<\?php\s+foreach\s*\((.*?)\):\s*\?>', r'@foreach (\1)', content)
    content = content.replace("<?php endforeach; ?>", "@endforeach")
    content = re.sub(r'<\?php\s+if\s*\((.*?)\):\s*\?>', r'@if (\1)', content)
    content = re.sub(r'<\?php\s+elseif\s*\((.*?)\):\s*\?>', r'@elseif (\1)', content)
    content = content.replace("<?php else: ?>", "@else")
    content = content.replace("<?php endif; ?>", "@endif")
    content = content.replace("<?php $no = 1; ?>", "@php $no = 1; @endphp")
    content = re.sub(r'<\?php\s+\$no\s*=\s*1;\s*foreach\s*\((.*?)\):\s*\?>', r'@php $no = 1; @endphp\n@foreach (\1)', content)

    # Convert esc() and echo
    content = re.sub(r'<\?=\s*esc\((.*?)\)\s*\?>', r'{{ \1 }}', content)
    content = re.sub(r'<\?=\s*(.*?)\s*\?>', r'{!! \1 !!}', content)
    
    # base_url
    content = re.sub(r"base_url\('(.*?)'\)", r"url('\1')", content)

    with open(filepath, 'w') as f:
        f.write(content)

print("Conversion done.")
