# -*- coding: utf-8 -*-
import os, sys
from PIL import Image, ImageChops, ImageDraw, ImageFilter

def cut_tile(master_path):
    im = Image.open(master_path).convert('RGBA')
    W0, H0 = im.size
    side = max(W0, H0)
    canvas = Image.new('RGBA', (W0 + side, H0 + side), (0, 0, 0, 0))
    canvas.paste(im, (side // 2, side // 2))
    r, g, b, a = canvas.split()
    diff = ImageChops.subtract(r, b)          # коралл отделяется от серого фона по R-B
    m = diff.point(lambda v: 255 if v > 35 else 0)
    x0, y0, x1, y1 = m.getbbox()
    cx, cy = (x0 + x1) // 2, (y0 + y1) // 2
    box = (cx - side // 2, cy - side // 2, cx + side // 2, cy + side // 2)
    inv = ImageChops.invert(m)                # дыры внутри плитки (буквы, волна):
    ImageDraw.floodfill(inv, (0, 0), 128)     # внешнее заливается от угла, дыры остаются
    holes = inv.point(lambda v: 255 if v == 255 else 0)
    alpha = ImageChops.lighter(m, holes)
    alpha = alpha.filter(ImageFilter.MinFilter(3)).filter(ImageFilter.GaussianBlur(1.2))
    tile = canvas.crop(box).resize((1024, 1024), Image.LANCZOS)
    alpha = alpha.crop(box).resize((1024, 1024), Image.LANCZOS)
    tile.putalpha(alpha)
    return tile

def fullbleed(tile, S):
    # Правка: координаты сэмплов берём в системе координат самой плитки (1024),
    # иначе для S=180 попадаем в прозрачный скруглённый угол и получаем чёрный фон.
    rgb = tile.convert('RGB')
    W, H = rgb.size
    top = rgb.getpixel((W // 2, int(H * 0.10)))
    bot = rgb.getpixel((W // 2, int(H * 0.90)))
    bg = Image.new('RGB', (S, S))
    d = ImageDraw.Draw(bg)
    for y in range(S):
        t = y / (S - 1)
        d.line([(0, y), (S, y)], fill=tuple(int(top[i] + (bot[i] - top[i]) * t) for i in range(3)))
    return bg

def save_all(tile, out):
    os.makedirs(out, exist_ok=True)
    for s in (512, 192):
        tile.resize((s, s), Image.LANCZOS).save(os.path.join(out, 'icon-%d.png' % s))
    t180 = tile.resize((180, 180), Image.LANCZOS)
    fb = fullbleed(tile, 180); fb.paste(t180, (0, 0), t180)
    fb.save(os.path.join(out, 'apple-touch-icon-180.png'))
    for s in (512, 192):
        bg = fullbleed(tile, s)
        t = tile.resize((int(s * 0.78),) * 2, Image.LANCZOS)
        bg.paste(t, ((s - t.width) // 2,) * 2, t)
        bg.save(os.path.join(out, 'maskable-%d.png' % s))
    tile.resize((256, 256), Image.LANCZOS).save(os.path.join(out, 'favicon.ico'),
        format='ICO', sizes=[(16, 16), (32, 32), (48, 48)])
    for s in (32, 16):
        tile.resize((s, s), Image.LANCZOS).save(os.path.join(out, 'favicon-%d.png' % s))

if __name__ == '__main__':
    master = sys.argv[1] if len(sys.argv) > 1 else '1026.png'
    out = sys.argv[2] if len(sys.argv) > 2 else 'icons'
    save_all(cut_tile(master), out)
    print('OK:', out)
