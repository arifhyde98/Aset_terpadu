import os
import glob
import re

files = glob.glob('/home/arif/Projek/SIPAT_Terpadu/resources/views/sipat/*/*.blade.php')

for filepath in files:
    with open(filepath, 'r') as f:
        content = f.read()

    content = re.sub(r'<\?php\s+foreach\s*\((.*?)\)\s*:\s*\?>', r'@foreach (\1)', content)
    content = content.replace("<?php endforeach; ?>", "@endforeach")
    content = re.sub(r'<\?php\s+if\s*\((.*?)\)\s*:\s*\?>', r'@if (\1)', content)
    content = re.sub(r'<\?php\s+elseif\s*\((.*?)\)\s*:\s*\?>', r'@elseif (\1)', content)
    content = content.replace("<?php else : ?>", "@else")
    content = content.replace("<?php else: ?>", "@else")
    content = content.replace("<?php endif; ?>", "@endif")

    with open(filepath, 'w') as f:
        f.write(content)

print("Fix done.")
