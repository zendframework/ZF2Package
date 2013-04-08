<?xml version="1.0"?>
<xsl:stylesheet version="1.0"
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

<xsl:output method="xml" encoding="utf-8" indent="no"/>

<xsl:template match="@*|*" priority="-1">
    <xsl:copy>
      <xsl:apply-templates select="@*|node()"/>
    </xsl:copy>
</xsl:template>

<xsl:template match="processing-instruction()|comment()">
    <xsl:copy/>
</xsl:template>


<xsl:template match="part"/>
<xsl:template match="part[@id='reference']">
    <xsl:apply-templates select="chapter"/>
</xsl:template>

<xsl:template match="chapter"/>
<xsl:template match="chapter[@id='###chapter.id###']">
    <xsl:copy>
      <xsl:apply-templates select="@*|node()"/>
    </xsl:copy>
</xsl:template>

<xsl:template match="appendix"/>

<xsl:template match="/book/xi:include" xmlns:xi="http://www.w3.org/2001/XInclude"/>

</xsl:stylesheet>
