<?php
/**
 * Minimal .xlsx writer (no Composer libraries on the server; needs PHP's zip extension).
 *
 * Each sheet: ['name' => 'Ofisler', 'columns' => [['title' => 'Ülke', 'width' => 18, 'type' => 'text'|'number'|'link'|'status'|'wrap'], ...],
 *              'rows' => [[cell, cell, ...], ...]]
 * A 'link' cell is ['url' => ..., 'text' => ...] (or a plain URL string).
 * Every sheet gets a styled header row, frozen header, filters and the given column widths.
 */

function xlsx_esc(string $s): string {
    // Drop characters XML 1.0 does not allow, then escape
    $s = preg_replace('/[^\x{9}\x{A}\x{D}\x{20}-\x{D7FF}\x{E000}-\x{FFFD}\x{10000}-\x{10FFFF}]/u', '', $s) ?? '';
    return htmlspecialchars($s, ENT_QUOTES | ENT_XML1, 'UTF-8');
}

function xlsx_col(int $i): string {
    $s = '';
    for ($i++; $i > 0; $i = intdiv($i - 1, 26)) $s = chr(65 + ($i - 1) % 26) . $s;
    return $s;
}

/** Style ids, matching the cellXfs list in xlsx_styles() */
const XLSX_S_TEXT = 1, XLSX_S_HEAD = 2, XLSX_S_WRAP = 3, XLSX_S_NUM = 4, XLSX_S_LINK = 5,
      XLSX_S_ACTIVE = 6, XLSX_S_PASSIVE = 7, XLSX_S_TITLE = 8;

function xlsx_styles(): string {
    return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
        . '<numFmts count="1"><numFmt numFmtId="164" formatCode="0.0000000"/></numFmts>'
        . '<fonts count="6">'
        . '<font><sz val="10"/><name val="Calibri"/><family val="2"/></font>'
        . '<font><b/><sz val="10"/><color rgb="FFFFFFFF"/><name val="Calibri"/><family val="2"/></font>'
        . '<font><u/><sz val="10"/><color rgb="FF1D4ED8"/><name val="Calibri"/><family val="2"/></font>'
        . '<font><b/><sz val="10"/><color rgb="FF166534"/><name val="Calibri"/><family val="2"/></font>'
        . '<font><b/><sz val="10"/><color rgb="FF6B7280"/><name val="Calibri"/><family val="2"/></font>'
        . '<font><b/><sz val="10"/><color rgb="FF111827"/><name val="Calibri"/><family val="2"/></font>'
        . '</fonts>'
        . '<fills count="5">'
        . '<fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill>'
        . '<fill><patternFill patternType="solid"><fgColor rgb="FF1E3A5F"/><bgColor indexed="64"/></patternFill></fill>'
        . '<fill><patternFill patternType="solid"><fgColor rgb="FFDCFCE7"/><bgColor indexed="64"/></patternFill></fill>'
        . '<fill><patternFill patternType="solid"><fgColor rgb="FFF3F4F6"/><bgColor indexed="64"/></patternFill></fill>'
        . '</fills>'
        . '<borders count="2"><border><left/><right/><top/><bottom/><diagonal/></border>'
        . '<border><left style="thin"><color rgb="FFE5E7EB"/></left><right style="thin"><color rgb="FFE5E7EB"/></right>'
        . '<top style="thin"><color rgb="FFE5E7EB"/></top><bottom style="thin"><color rgb="FFE5E7EB"/></bottom><diagonal/></border></borders>'
        . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
        . '<cellXfs count="9">'
        . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
        // 1 text
        . '<xf numFmtId="49" fontId="0" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyBorder="1" applyAlignment="1"><alignment vertical="top"/></xf>'
        // 2 header
        . '<xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment vertical="center" wrapText="1"/></xf>'
        // 3 wrapped text
        . '<xf numFmtId="49" fontId="0" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyBorder="1" applyAlignment="1"><alignment vertical="top" wrapText="1"/></xf>'
        // 4 number
        . '<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1" applyAlignment="1"><alignment horizontal="right" vertical="top"/></xf>'
        // 5 link
        . '<xf numFmtId="0" fontId="2" fillId="0" borderId="1" xfId="0" applyFont="1" applyBorder="1" applyAlignment="1"><alignment vertical="top"/></xf>'
        // 6 active badge
        . '<xf numFmtId="0" fontId="3" fillId="3" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="top"/></xf>'
        // 7 passive badge
        . '<xf numFmtId="0" fontId="4" fillId="4" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="top"/></xf>'
        // 8 bold text (key column)
        . '<xf numFmtId="49" fontId="5" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyFont="1" applyBorder="1" applyAlignment="1"><alignment vertical="top"/></xf>'
        . '</cellXfs>'
        . '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
        . '</styleSheet>';
}

function xlsx_text_cell(string $ref, string $value, int $style): string {
    if ($value === '') return '<c r="' . $ref . '" s="' . $style . '"/>';
    return '<c r="' . $ref . '" s="' . $style . '" t="inlineStr"><is><t xml:space="preserve">' . xlsx_esc($value) . '</t></is></c>';
}

function xlsx_sheet_xml(array $sheet): string {
    $cols = $sheet['columns'];
    $n = count($cols);
    $last = xlsx_col(max(0, $n - 1));
    $rowCount = count($sheet['rows']) + 1;

    $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
        . '<dimension ref="A1:' . $last . $rowCount . '"/>'
        . '<sheetViews><sheetView workbookViewId="0"' . (!empty($sheet['first']) ? ' tabSelected="1"' : '') . '>'
        . '<pane xSplit="' . (int)($sheet['freeze_cols'] ?? 0) . '" ySplit="1" topLeftCell="' . xlsx_col((int)($sheet['freeze_cols'] ?? 0)) . '2" activePane="bottomRight" state="frozen"/>'
        . '<selection pane="bottomRight"/></sheetView></sheetViews>'
        . '<sheetFormatPr defaultRowHeight="15"/><cols>';
    foreach ($cols as $i => $c) {
        $xml .= '<col min="' . ($i + 1) . '" max="' . ($i + 1) . '" width="' . (float)($c['width'] ?? 14) . '" customWidth="1"/>';
    }
    $xml .= '</cols><sheetData>';

    $xml .= '<row r="1" ht="30" customHeight="1">';
    foreach ($cols as $i => $c) $xml .= xlsx_text_cell(xlsx_col($i) . '1', (string)$c['title'], XLSX_S_HEAD);
    $xml .= '</row>';

    foreach ($sheet['rows'] as $ri => $row) {
        $r = $ri + 2;
        $xml .= '<row r="' . $r . '">';
        foreach ($cols as $i => $c) {
            $ref = xlsx_col($i) . $r;
            $v = $row[$i] ?? null;
            $type = $c['type'] ?? 'text';
            if ($type === 'number') {
                $xml .= ($v === null || $v === '' || !is_numeric($v))
                    ? xlsx_text_cell($ref, (string)$v, XLSX_S_NUM)
                    : '<c r="' . $ref . '" s="' . XLSX_S_NUM . '"><v>' . (0 + $v) . '</v></c>';
            } elseif ($type === 'link') {
                $url = is_array($v) ? (string)($v['url'] ?? '') : (string)$v;
                $text = is_array($v) ? (string)($v['text'] ?? $url) : $url;
                if ($url === '' || !preg_match('#^https?://#i', $url) || strlen($url) > 250) {
                    $xml .= xlsx_text_cell($ref, $text, XLSX_S_TEXT);
                } else {
                    $f = 'HYPERLINK("' . str_replace('"', '""', $url) . '","' . str_replace('"', '""', $text) . '")';
                    $xml .= '<c r="' . $ref . '" s="' . XLSX_S_LINK . '" t="str"><f>' . xlsx_esc($f) . '</f><v>' . xlsx_esc($text) . '</v></c>';
                }
            } elseif ($type === 'status') {
                $on = (bool)$v;
                $xml .= xlsx_text_cell($ref, $on ? 'Aktif' : 'Pasif', $on ? XLSX_S_ACTIVE : XLSX_S_PASSIVE);
            } else {
                $style = $type === 'wrap' ? XLSX_S_WRAP : ($type === 'bold' ? XLSX_S_TITLE : XLSX_S_TEXT);
                $xml .= xlsx_text_cell($ref, (string)$v, $style);
            }
        }
        $xml .= '</row>';
    }
    $xml .= '</sheetData>';
    $xml .= '<autoFilter ref="A1:' . $last . $rowCount . '"/>';
    $xml .= '<pageMargins left="0.4" right="0.4" top="0.5" bottom="0.5" header="0.3" footer="0.3"/>';
    $xml .= '<pageSetup orientation="landscape" fitToHeight="0"/>';
    $xml .= '</worksheet>';
    return $xml;
}

/**
 * Build the workbook into a temp file and return its path (caller deletes it).
 */
function xlsx_build(array $sheets, string $title = ''): string {
    if (!class_exists('ZipArchive')) {
        throw new RuntimeException('Sunucuda PHP zip eklentisi yok; Excel dosyası oluşturulamıyor.');
    }
    $path = tempnam(sys_get_temp_dir(), 'xlsx');
    $zip = new ZipArchive();
    if ($zip->open($path, ZipArchive::OVERWRITE) !== true) {
        throw new RuntimeException('Excel dosyası oluşturulamadı.');
    }
    $n = count($sheets);
    $ct = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
        . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
        . '<Default Extension="xml" ContentType="application/xml"/>'
        . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
        . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
        . '<Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>'
        . '<Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>';
    for ($i = 1; $i <= $n; $i++) {
        $ct .= '<Override PartName="/xl/worksheets/sheet' . $i . '.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
    }
    $zip->addFromString('[Content_Types].xml', $ct . '</Types>');

    $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
        . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
        . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>'
        . '<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/>'
        . '</Relationships>');

    $now = gmdate('Y-m-d\TH:i:s\Z');
    $zip->addFromString('docProps/core.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'
        . '<dc:title>' . xlsx_esc($title) . '</dc:title><dc:creator>Acıbadem Admin</dc:creator>'
        . '<dcterms:created xsi:type="dcterms:W3CDTF">' . $now . '</dcterms:created>'
        . '<dcterms:modified xsi:type="dcterms:W3CDTF">' . $now . '</dcterms:modified>'
        . '</cp:coreProperties>');
    $zip->addFromString('docProps/app.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties"><Application>Microsoft Excel</Application></Properties>');

    $wb = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
        . '<bookViews><workbookView/></bookViews><sheets>';
    $rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">';
    $defined = '';
    foreach (array_values($sheets) as $i => $sheet) {
        $id = $i + 1;
        $name = mb_substr(preg_replace('#[\[\]\*\?/\\\\:]#', ' ', (string)$sheet['name']), 0, 31);
        $wb .= '<sheet name="' . xlsx_esc($name) . '" sheetId="' . $id . '" r:id="rId' . $id . '"/>';
        $rels .= '<Relationship Id="rId' . $id . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet' . $id . '.xml"/>';
        $last = xlsx_col(max(0, count($sheet['columns']) - 1));
        $defined .= '<definedName name="_xlnm._FilterDatabase" localSheetId="' . $i . '" hidden="1">\'' . xlsx_esc(str_replace("'", "''", $name)) . '\'!$A$1:$' . $last . '$' . (count($sheet['rows']) + 1) . '</definedName>';
        $sheet['first'] = $i === 0;
        $zip->addFromString('xl/worksheets/sheet' . $id . '.xml', xlsx_sheet_xml($sheet));
    }
    $wb .= '</sheets><definedNames>' . $defined . '</definedNames></workbook>';
    $rels .= '<Relationship Id="rId' . ($n + 1) . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>';
    $zip->addFromString('xl/workbook.xml', $wb);
    $zip->addFromString('xl/_rels/workbook.xml.rels', $rels);
    $zip->addFromString('xl/styles.xml', xlsx_styles());
    $zip->close();
    return $path;
}
