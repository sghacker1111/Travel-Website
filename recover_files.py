import json
import os
import re

transcript_path = r"C:\Users\SGHACKER\.gemini\antigravity\brain\9c814c8b-ab84-4384-a6ff-3cf157a08265\.system_generated\logs\transcript.jsonl"
workspace_dir = r"d:\A WebSite\Travel Website"

recover_targets = {
    'settings.json': 'data/settings.json',
    'about.php': 'about.php',
    'admin.php': 'admin.php',
    'blog.php': 'blog.php',
    'contact.php': 'contact.php',
    'index.php': 'index.php',
    'package.php': 'package.php',
    'peak-climbing.php': 'peak-climbing.php',
    'route.php': 'route.php',
    'tour.php': 'tour.php',
    'trekking.php': 'trekking.php',
    'header.php': 'includes/header.php'
}

targets_map = {k.lower(): v for k, v in recover_targets.items()}
file_contents = {}

with open(transcript_path, 'r', encoding='utf-8', errors='ignore') as f:
    for line in f:
        try:
            step = json.loads(line)
            tool_calls = step.get('tool_calls', [])
            if not tool_calls:
                continue
            for tc in tool_calls:
                name = tc.get('name')
                args = tc.get('args', {})
                
                if name == 'write_to_file':
                    target = args.get('TargetFile', '')
                    content = args.get('CodeContent', '')
                    
                    # Clean up quotes if present as raw strings
                    if target.startswith('"') and target.endswith('"'):
                        target = json.loads(target)
                    if content.startswith('"') and content.endswith('"'):
                        try:
                            content = json.loads(content)
                        except:
                            # Fallback if it is not valid JSON
                            content = content[1:-1].encode().decode('unicode_escape')
                    
                    if target and content:
                        basename = os.path.basename(target).lower()
                        if basename in targets_map:
                            file_contents[targets_map[basename]] = content
                            print(f"Captured write_to_file for {targets_map[basename]}")
                            
        except Exception as e:
            print(f"Error parsing line: {e}")

# Write back
for rel_path, content in file_contents.items():
    full_path = os.path.join(workspace_dir, rel_path.replace('/', '\\'))
    os.makedirs(os.path.dirname(full_path), exist_ok=True)
    with open(full_path, 'w', encoding='utf-8') as f:
        f.write(content)
    print(f"Recovered {full_path} (length: {len(content)})")

print("Recovery finished!")
