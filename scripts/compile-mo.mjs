import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import gettextParser from 'gettext-parser';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const poPath = path.join(root, 'languages', 'navitto-ja.po');
const moPath = path.join(root, 'languages', 'navitto-ja.mo');

const po = fs.readFileSync(poPath);
const parsed = gettextParser.po.parse(po);
fs.writeFileSync(moPath, gettextParser.mo.compile(parsed));

console.log(`Wrote ${moPath}`);
