import os
import glob
import re

files = glob.glob('/home/arif/Projek/SIPAT_Terpadu/resources/views/sipat/*/*.blade.php')

for filepath in files:
    with open(filepath, 'r') as f:
        content = f.read()

    # Cards
    content = content.replace('class="card ', 'class="card clean-card ')
    content = content.replace("class='card ", "class='card clean-card ")
    content = content.replace('class="card"', 'class="card clean-card"')
    
    # Avoid duplicate clean-card
    content = content.replace('clean-card clean-card', 'clean-card')
    content = content.replace('fancy-card', 'clean-card')

    # Backgrounds in cards
    content = content.replace('bg-white', 'bg-transparent')
    content = content.replace('bg-light', 'bg-body-tertiary')
    
    # Tables
    content = content.replace('table-responsive bg-white', 'table-responsive')
    
    # Text colors that look bad in dark mode
    content = content.replace('text-dark', 'text-body')
    content = content.replace('text-muted', 'text-secondary')

    with open(filepath, 'w') as f:
        f.write(content)

print("UI fixes applied.")
