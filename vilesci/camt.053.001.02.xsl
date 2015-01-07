<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:myns="ISO:camt.053.001.02:APC:STUZZA:payments:003">
<xsl:output method="text" encoding="UTF-8"/>
	
	<xsl:template match="myns:Document">
		<xsl:text>"EmpfIBAN","EmpfBIC","Betrag","Datum","TransaktionsId","Name","Land","Adresse","Verwendungszweck","Zahlungsreferenz"&#xa;</xsl:text>
		<xsl:apply-templates select="myns:BkToCstmrStmt" />
	</xsl:template>

	<xsl:template match="myns:BkToCstmrStmt">
		<xsl:apply-templates select="myns:Stmt" />
	</xsl:template>
	<xsl:template match="myns:Stmt">
		<xsl:apply-templates select="myns:Ntry" />
	</xsl:template>

	<xsl:template match="myns:Ntry">
		<!-- nur CRDT (Zahlungseingang) und BOOK (tatsaechlich gebuchte) Eintraege uebernehmen -->
		<xsl:if test="myns:CdtDbtInd='CRDT' and myns:Sts='BOOK'">
			<xsl:text>"</xsl:text>

			<!-- Empfaenger IBAN -->
			<xsl:value-of select="../myns:Acct/myns:Id/myns:IBAN"/><xsl:text>","</xsl:text>

			<!-- Empfaenger BIC -->
			<xsl:value-of select="../myns:Acct/myns:Svcr/myns:FinInstnId/myns:BIC"/><xsl:text>","</xsl:text>

			<!-- Betrag -->
			<xsl:value-of select="myns:Amt"/><xsl:text>","</xsl:text>

			<!-- Buchungsdatum -->
			<xsl:value-of select="myns:BookgDt/myns:Dt"/><xsl:text>","</xsl:text>

			<!-- TransaktionsID -->
			<xsl:value-of select="myns:NtryDtls/myns:TxDtls/myns:Refs/myns:TxId"/><xsl:text>","</xsl:text>
			
			<!-- Daten des Einzahlers -->
			<!-- Name -->
			<xsl:value-of select="myns:NtryDtls/myns:TxDtls/myns:RltdPties/myns:Dbtr/myns:Nm"/><xsl:text>","</xsl:text>
			<!-- Land -->
			<xsl:value-of select="myns:NtryDtls/myns:TxDtls/myns:RltdPties/myns:Dbtr/myns:PstlAdr/myns:Ctry"/><xsl:text>","</xsl:text>
			<!-- Adresse -->
			<xsl:value-of select="myns:NtryDtls/myns:TxDtls/myns:RltdPties/myns:Dbtr/myns:PstlAdr/myns:AdrLine"/><xsl:text>","</xsl:text>

			<!-- Unstructured Reference (Verwendungszweck) -->
			<xsl:value-of select="myns:NtryDtls/myns:TxDtls/myns:RmtInf/myns:Ustrd"/><xsl:text>","</xsl:text>
			<!-- Structured Reference (Zahlungsreferenz) -->
			<xsl:value-of select="myns:NtryDtls/myns:TxDtls/myns:RmtInf/myns:Strd/myns:CdtrRefInf/myns:Ref"/>

			<xsl:text>"&#xa;</xsl:text>
		</xsl:if>
	</xsl:template>
</xsl:stylesheet>

