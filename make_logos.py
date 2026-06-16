from PIL import Image, ImageDraw, ImageFont
import os

# Ensure directory exists
output_dir = "assets/images/"
if not os.path.exists(output_dir):
    os.makedirs(output_dir)

def create_logo(size, filename):
    # 1. Create a Black Background
    img = Image.new('RGB', (size, size), color=(0, 0, 0))
    d = ImageDraw.Draw(img)

    # 2. Draw a Cyan Border (Sci-Fi Style)
    border_width = int(size * 0.05)
    d.rectangle(
        [0, 0, size-1, size-1], 
        outline=(0, 243, 255), 
        width=border_width
    )

    # 3. Draw the "V" Text in the center
    # Note: If this fails, it usually means arial.ttf isn't found. 
    # In that case, we draw a simple shape instead.
    try:
        font_size = int(size * 0.6)
        font = ImageFont.truetype("arial.ttf", font_size)
        
        # Calculate text position to center it
        bbox = d.textbbox((0, 0), "V", font=font)
        text_w = bbox[2] - bbox[0]
        text_h = bbox[3] - bbox[1]
        x = (size - text_w) / 2
        y = (size - text_h) / 2 - (size * 0.1) # Shift up slightly
        
        d.text((x, y), "V", fill=(0, 243, 255), font=font)
    except:
        # Fallback: Draw a Circle if font fails
        margin = int(size * 0.2)
        d.ellipse([margin, margin, size-margin, size-margin], fill=(0, 243, 255))

    # 4. Save
    img.save(os.path.join(output_dir, filename))
    print(f"✅ Generated: {filename}")

# Generate both sizes
try:
    create_logo(192, "logo_192.png")
    create_logo(512, "logo_512.png")
    print("\nLogos created successfully in assets/images/ folder!")
except ImportError:
    print("ERROR: You need the Pillow library. Run: pip install Pillow")