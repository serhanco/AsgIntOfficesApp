<?xml version="1.0" encoding="UTF-8"?>
<!-- Human-readable view of sitemap.xml. Browsers apply it; search engines read the raw XML and ignore it. -->
<xsl:stylesheet version="1.0"
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
    xmlns:sm="http://www.sitemaps.org/schemas/sitemap/0.9"
    xmlns:xhtml="http://www.w3.org/1999/xhtml">
<xsl:output method="html" encoding="UTF-8" indent="yes" doctype-system="about:legacy-compat"/>

<xsl:template match="/">
<html lang="tr">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <title>Site haritası | Acıbadem International Offices</title>
    <style>
        :root { --navy: #092c74; --ink: #1d2433; --muted: #5b6475; --line: #e3e8f0; --soft: #f5f8fc; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: "Plus Jakarta Sans", "Inter", system-ui, -apple-system, "Segoe UI", sans-serif; color: var(--ink); background: #fff; line-height: 1.5; }
        header { background: var(--navy); color: #fff; padding: 28px 16px; }
        header h1 { margin: 0; font-size: 1.6rem; }
        header p { margin: 6px 0 0; color: #cfdcef; font-size: .95rem; }
        main { max-width: 1100px; margin: 0 auto; padding: 20px 16px 40px; }
        .count { color: var(--muted); font-size: .95rem; margin: 0 0 16px; }
        .table-wrap { overflow-x: auto; border: 1px solid var(--line); border-radius: 12px; }
        table { width: 100%; border-collapse: collapse; font-size: .92rem; }
        th, td { text-align: left; padding: 10px 12px; border-bottom: 1px solid var(--line); vertical-align: top; }
        th { background: var(--soft); color: var(--navy); font-weight: 700; position: sticky; top: 0; }
        tr:last-child td { border-bottom: 0; }
        td.addr { overflow-wrap: anywhere; min-width: 220px; }
        td.addr a { color: var(--navy); font-weight: 600; text-decoration: none; }
        td.addr a:hover, td.addr a:focus { text-decoration: underline; }
        td.langs a { display: inline-block; margin: 0 6px 6px 0; padding: 2px 8px; border-radius: 999px; background: var(--soft); color: var(--navy); text-decoration: none; font-size: .82rem; }
        td.langs a:hover, td.langs a:focus { background: #dfe9f7; }
        td.date { white-space: nowrap; color: var(--muted); }
        @media (max-width: 640px) {
            th, td { padding: 8px; }
            td.date { white-space: normal; }
        }
    </style>
</head>
<body>
<header>
    <h1>Site haritası</h1>
    <p>Sitenin tüm açık sayfaları, her biri için dil sürümleriyle birlikte.</p>
</header>
<main>
    <p class="count">
        <xsl:value-of select="count(sm:urlset/sm:url[not(contains(sm:loc, '?'))])"/> sayfa
        · her sayfanın dil sürümleri aşağıda listeli.
    </p>
    <div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th scope="col">Sayfa</th>
                <th scope="col">Dil sürümleri</th>
                <th scope="col">Son güncelleme</th>
            </tr>
        </thead>
        <tbody>
            <xsl:for-each select="sm:urlset/sm:url[not(contains(sm:loc, '?'))]">
                <tr>
                    <td class="addr"><a href="{sm:loc}"><xsl:value-of select="sm:loc"/></a></td>
                    <td class="langs">
                        <xsl:for-each select="xhtml:link[@rel='alternate' and @hreflang!='x-default']">
                            <a href="{@href}" hreflang="{@hreflang}"><xsl:value-of select="@hreflang"/></a>
                        </xsl:for-each>
                    </td>
                    <td class="date"><xsl:value-of select="sm:lastmod"/></td>
                </tr>
            </xsl:for-each>
        </tbody>
    </table>
    </div>
</main>
</body>
</html>
</xsl:template>
</xsl:stylesheet>
