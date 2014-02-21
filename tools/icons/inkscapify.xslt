<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0"
  xmlns="http://www.w3.org/2000/svg"
  xmlns:inkscape="http://www.inkscape.org/namespaces/inkscape"
  xmlns:svg="http://www.w3.org/2000/svg" 
  xmlns:xlink="http://www.w3.org/1999/xlink"
  xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
  exclude-result-prefixes="svg">

  <xsl:template match="svg:g[@id]">
    <g inkscape:label="{@id}" inkscape:groupmode="layer">
      <xsl:copy-of select="@*"/>
      <xsl:apply-templates/>
    </g>
  </xsl:template>

  <xsl:template match="svg:path[@id]">
    <g inkscape:label="{@id}" inkscape:groupmode="layer">
      <xsl:copy-of select="."/>
    </g>
  </xsl:template>

  <xsl:template match="*">
    <xsl:element name="{local-name(.)}">
      <xsl:copy-of select="@*"/>
      <xsl:apply-templates/>
    </xsl:element>
  </xsl:template>
</xsl:stylesheet>
