<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:fo="http://www.w3.org/1999/XSL/Format" xmlns:zs="http://www.loc.gov/zing/srw/">
<xsl:output 
 method="xml"
 encoding="UTF-8"/>

<xsl:param name="sortBy"/>
<xsl:param name="sortOrder"/>

<xsl:template match="/">

	<xsl:element name="records">

		<xsl:choose>
			<xsl:when test="$sortBy='author'">
				<xsl:apply-templates select="//record">
					<xsl:sort select="datafield[@tag=100]/subfield[@code='a']" data-type="text" order="{$sortOrder}"/>
				</xsl:apply-templates>
			</xsl:when>
			<xsl:when test="$sortBy='year'">
				<xsl:apply-templates select="//record">
					<xsl:sort select="translate(datafield[@tag=260]/subfield[@code='c'], 'cop.[]', '')" data-type="text" order="{$sortOrder}"/>
				</xsl:apply-templates>
			</xsl:when>
			<xsl:when test="$sortBy='title'">
				<xsl:apply-templates select="//record">
					<xsl:sort select="datafield[@tag=245]/subfield[@code='a']" data-type="text" order="{$sortOrder}"/>
				</xsl:apply-templates>
			</xsl:when>
		</xsl:choose>
	
	</xsl:element>

</xsl:template>

<xsl:template match="//record">
	<xsl:copy-of select="."/>
</xsl:template>

</xsl:stylesheet>