import os
import zipfile

dist_dir = 'dist'
zip_filename = 'hostinger-deploy.zip'

if os.path.exists(zip_filename):
    os.remove(zip_filename)

with zipfile.ZipFile(zip_filename, 'w', zipfile.ZIP_DEFLATED) as zipf:
    for root, dirs, files in os.walk(dist_dir):
        for file in files:
            file_path = os.path.join(root, file)
            # CRITICAL: Always use Unix forward slashes '/' so Linux / Hostinger extracts directories properly!
            arcname = os.path.relpath(file_path, dist_dir).replace('\\', '/')
            zipf.write(file_path, arcname)

file_count = len(zipf.namelist())
zip_size = os.path.getsize(zip_filename)
print(f"Created {zip_filename} with {file_count} files ({zip_size} bytes) using Unix path separators.")
