import glob
import re

files = glob.glob('/home/arif/Projek/SIPAT_Terpadu/resources/views/sipat/**/*.blade.php', recursive=True)

for filepath in files:
    with open(filepath, 'r') as f:
        content = f.read()

    # Clean up duplicated clean-card
    content = content.replace('clean-card clean-card', 'clean-card')
    content = content.replace('card clean-card clean-card', 'card clean-card')
    content = content.replace('border-0 clean-card', 'border-0')
    
    # Fix form selects and inputs with forced bg-body-tertiary
    content = content.replace('form-select bg-body-tertiary border-0', 'form-select')
    content = content.replace('form-control bg-body-tertiary border-0', 'form-control')
    content = content.replace('bg-body-tertiary', 'bg-body')

    with open(filepath, 'w') as f:
        f.write(content)

print("SIPAT view classes cleaned up successfully.")
