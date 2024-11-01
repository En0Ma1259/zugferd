<?php

namespace horstoeko\zugferd\tests\testcases;

use horstoeko\zugferd\codelists\ZugferdPaymentMeans;
use horstoeko\zugferd\exception\ZugferdFileNotFoundException;
use horstoeko\zugferd\exception\ZugferdUnknownMimetype;
use horstoeko\zugferd\tests\TestCase;
use horstoeko\zugferd\tests\traits\HandlesXmlTests;
use horstoeko\zugferd\ZugferdDocumentBuilder;
use horstoeko\zugferd\ZugferdDocumentPdfBuilder;
use horstoeko\zugferd\ZugferdDocumentPdfBuilderAbstract;
use horstoeko\zugferd\ZugferdPackageVersion;
use horstoeko\zugferd\ZugferdProfiles;
use setasign\Fpdi\PdfParser\PdfParserException;
use setasign\Fpdi\PdfParser\StreamReader;
use Smalot\PdfParser\Parser as PdfParser;

class PdfBuilderEn16931Test extends TestCase
{
    use HandlesXmlTests;

    /**
     * Source pdf filename
     *
     * @var string
     */
    protected static $sourcePdfFilename = "";

    /**
     * Destination pdf filename
     *
     * @var string
     */
    protected static $destPdfFilename = "";

    public static function setUpBeforeClass(): void
    {
        self::$sourcePdfFilename = dirname(__FILE__) . "/../assets/pdf_plain.pdf";
        self::$destPdfFilename = dirname(__FILE__) . "/../assets/GeneratedPDF.pdf";

        self::$document = (ZugferdDocumentBuilder::CreateNew(ZugferdProfiles::PROFILE_EN16931))
            ->setDocumentInformation("471102", "380", \DateTime::createFromFormat("Ymd", "20180305"), "EUR")
            ->addDocumentNote('Rechnung gemäß Bestellung vom 01.03.2018.')
            ->addDocumentNote('Lieferant GmbH' . PHP_EOL . 'Lieferantenstraße 20' . PHP_EOL . '80333 München' . PHP_EOL . 'Deutschland' . PHP_EOL . 'Geschäftsführer: Hans Muster' . PHP_EOL . 'Handelsregisternummer: H A 123' . PHP_EOL . PHP_EOL, null, 'REG')
            ->setDocumentSupplyChainEvent(\DateTime::createFromFormat('Ymd', '20180305'))
            ->addDocumentPaymentMean(ZugferdPaymentMeans::UNTDID_4461_58, null, null, null, null, null, "DE12500105170648489890", null, null, null)
            ->setDocumentSeller("Lieferant GmbH", "549910")
            ->addDocumentSellerGlobalId("4000001123452", "0088")
            ->addDocumentSellerTaxRegistration("FC", "201/113/40209")
            ->addDocumentSellerTaxRegistration("VA", "DE123456789")
            ->setDocumentSellerAddress("Lieferantenstraße 20", "", "", "80333", "München", "DE")
            ->setDocumentSellerContact("Heinz Mükker", "Buchhaltung", "+49-111-2222222", "+49-111-3333333", "info@lieferant.de")
            ->setDocumentBuyer("Kunden AG Mitte", "GE2020211")
            ->setDocumentBuyerReference("34676-342323")
            ->setDocumentBuyerAddress("Kundenstraße 15", "", "", "69876", "Frankfurt", "DE")
            ->addDocumentTax("S", "VAT", 275.0, 19.25, 7.0)
            ->addDocumentTax("S", "VAT", 198.0, 37.62, 19.0)
            ->setDocumentSummation(529.87, 529.87, 473.00, 0.0, 0.0, 473.00, 56.87, null, 0.0)
            ->addDocumentPaymentTerm("Zahlbar innerhalb 30 Tagen netto bis 04.04.2018, 3% Skonto innerhalb 10 Tagen bis 15.03.2018")
            ->addNewPosition("1")
            ->setDocumentPositionNote("Bemerkung zu Zeile 1")
            ->setDocumentPositionProductDetails("Trennblätter A4", "", "TB100A4", null, "0160", "4012345001235")
            ->addDocumentPositionProductCharacteristic("Farbe", "Gelb")
            ->addDocumentPositionProductClassification("ClassCode", "ClassName", "ListId", "ListVersionId")
            ->setDocumentPositionProductOriginTradeCountry("CN")
            ->setDocumentPositionGrossPrice(9.9000)
            ->setDocumentPositionNetPrice(9.9000)
            ->setDocumentPositionQuantity(20, "H87")
            ->addDocumentPositionTax('S', 'VAT', 19)
            ->setDocumentPositionLineSummation(198.0)
            ->addNewPosition("2")
            ->setDocumentPositionNote("Bemerkung zu Zeile 2")
            ->setDocumentPositionProductDetails("Joghurt Banane", "", "ARNR2", null, "0160", "4000050986428")
            ->addDocumentPositionProductCharacteristic("Suesstoff", "Nein")
            ->addDocumentPositionProductClassification("ClassCode", "ClassName", "ListId", "ListVersionId")
            ->SetDocumentPositionGrossPrice(5.5000)
            ->SetDocumentPositionNetPrice(5.5000)
            ->SetDocumentPositionQuantity(50, "H87")
            ->AddDocumentPositionTax('S', 'VAT', 7)
            ->SetDocumentPositionLineSummation(275.0);
    }

    public static function tearDownAfterClass(): void
    {
        @unlink(self::$destPdfFilename);
    }

    /**
     * Tests
     */

    public function testBuildFromSourcePdfFile(): void
    {
        $pdfBuilder = new ZugferdDocumentPdfBuilder(self::$document, self::$sourcePdfFilename);
        $pdfBuilder->generateDocument();
        $pdfBuilder->saveDocument(self::$destPdfFilename);

        $this->assertTrue(file_exists(self::$destPdfFilename));
    }

    public function testBuildFromSourcePdfString(): void
    {
        $pdfBuilder = new ZugferdDocumentPdfBuilder(self::$document, self::$sourcePdfFilename);
        $pdfBuilder->generateDocument();
        $pdfBuilder->downloadString(self::$destPdfFilename);

        $this->assertIsString(self::$destPdfFilename);
    }

    public function testPdfMetaData(): void
    {
        $pdfBuilder = new ZugferdDocumentPdfBuilder(self::$document, self::$sourcePdfFilename);
        $pdfBuilder->generateDocument();
        $pdfBuilder->saveDocument(self::$destPdfFilename);

        $pdfParser = new PdfParser();
        $pdfParsed = $pdfParser->parseFile(self::$destPdfFilename);
        $pdfDetails = $pdfParsed->getDetails();

        $this->assertIsArray($pdfDetails);
        $this->assertArrayHasKey("Producer", $pdfDetails); //"FPDF 1.84"
        $this->assertArrayHasKey("CreationDate", $pdfDetails); //"2020-12-09T05:19:39+00:00"
        $this->assertArrayHasKey("Pages", $pdfDetails); //"1"
        $this->assertEquals("1", $pdfDetails["Pages"]);
    }

    public function testFromExistingPdfFile(): void
    {
        $pdfBuilder = ZugferdDocumentPdfBuilder::fromPdfFile(self::$document, self::$sourcePdfFilename);
        $pdfBuilder->generateDocument();
        $pdfBuilder->downloadString(self::$destPdfFilename);

        $this->assertIsString(self::$destPdfFilename);
    }

    public function testFromNotExistingPdfFile(): void
    {
        $this->expectException(ZugferdFileNotFoundException::class);

        ZugferdDocumentPdfBuilder::fromPdfFile(self::$document, '/tmp/anonexisting.pdf');
    }

    public function testFromPdfString(): void
    {
        $pdfString = file_get_contents(self::$sourcePdfFilename);

        $pdfBuilder = ZugferdDocumentPdfBuilder::fromPdfString(self::$document, $pdfString);
        $pdfBuilder->generateDocument();
        $pdfBuilder->downloadString(self::$destPdfFilename);

        $this->assertIsString(self::$destPdfFilename);
    }

    public function testFromPdfStringWhichIsInvalid(): void
    {
        $this->expectException(PdfParserException::class);
        $this->expectExceptionMessage('Unable to find PDF file header.');

        $pdfString = 'this_is_not_a_pdf_string';

        $pdfBuilder = ZugferdDocumentPdfBuilder::fromPdfString(self::$document, $pdfString);
        $pdfBuilder->generateDocument();
        $pdfBuilder->downloadString(self::$destPdfFilename);
    }

    public function testSetAdditionalCreatorTool(): void
    {
        $pdfBuilder = ZugferdDocumentPdfBuilder::fromPdfFile(self::$document, self::$sourcePdfFilename);
        $pdfBuilder->setAdditionalCreatorTool('Dummy');

        $toolName = sprintf('Factur-X PHP library v%s by HorstOeko', ZugferdPackageVersion::getInstalledVersion());

        $this->assertStringStartsWith('Dummy / Factur-X PHP library', $pdfBuilder->getCreatorToolName());
    }

    public function testSetAttachmentRelationshipTypeToUnknown(): void
    {
        $pdfBuilder = ZugferdDocumentPdfBuilder::fromPdfFile(self::$document, self::$sourcePdfFilename);
        $pdfBuilder->setAttachmentRelationshipType('unknown');

        $this->assertEquals(ZugferdDocumentPdfBuilder::AF_RELATIONSHIP_DATA, $pdfBuilder->getAttachmentRelationshipType());
    }

    public function testSetAttachmentRelationshipTypeToData(): void
    {
        $pdfBuilder = ZugferdDocumentPdfBuilder::fromPdfFile(self::$document, self::$sourcePdfFilename);
        $pdfBuilder->setAttachmentRelationshipType('Data');

        $this->assertEquals(ZugferdDocumentPdfBuilder::AF_RELATIONSHIP_DATA, $pdfBuilder->getAttachmentRelationshipType());
    }

    public function testSetAttachmentRelationshipTypeToAlternative(): void
    {
        $pdfBuilder = ZugferdDocumentPdfBuilder::fromPdfFile(self::$document, self::$sourcePdfFilename);
        $pdfBuilder->setAttachmentRelationshipType('Alternative');

        $this->assertEquals(ZugferdDocumentPdfBuilder::AF_RELATIONSHIP_ALTERNATIVE, $pdfBuilder->getAttachmentRelationshipType());
    }

    public function testSetAttachmentRelationshipTypeToSource(): void
    {
        $pdfBuilder = ZugferdDocumentPdfBuilder::fromPdfFile(self::$document, self::$sourcePdfFilename);
        $pdfBuilder->setAttachmentRelationshipType('Source');

        $this->assertEquals(ZugferdDocumentPdfBuilder::AF_RELATIONSHIP_SOURCE, $pdfBuilder->getAttachmentRelationshipType());
    }

    public function testSetAttachmentRelationshipTypeToDataDirect(): void
    {
        $pdfBuilder = ZugferdDocumentPdfBuilder::fromPdfFile(self::$document, self::$sourcePdfFilename);
        $pdfBuilder->setAttachmentRelationshipTypeToData();

        $this->assertEquals(ZugferdDocumentPdfBuilder::AF_RELATIONSHIP_DATA, $pdfBuilder->getAttachmentRelationshipType());
    }

    public function testSetAttachmentRelationshipTypeToAlternativeDirect(): void
    {
        $pdfBuilder = ZugferdDocumentPdfBuilder::fromPdfFile(self::$document, self::$sourcePdfFilename);
        $pdfBuilder->setAttachmentRelationshipTypeToAlternative();

        $this->assertEquals(ZugferdDocumentPdfBuilder::AF_RELATIONSHIP_ALTERNATIVE, $pdfBuilder->getAttachmentRelationshipType());
    }

    public function testSetAttachmentRelationshipTypeToSourceDirect(): void
    {
        $pdfBuilder = ZugferdDocumentPdfBuilder::fromPdfFile(self::$document, self::$sourcePdfFilename);
        $pdfBuilder->setAttachmentRelationshipTypeToSource();

        $this->assertEquals(ZugferdDocumentPdfBuilder::AF_RELATIONSHIP_SOURCE, $pdfBuilder->getAttachmentRelationshipType());
    }

    public function testAttachAdditionalFileFileDoesNotExist(): void
    {
        $filename = dirname(__FILE__) . '/unknown.txt';

        $this->expectException(ZugferdFileNotFoundException::class);
        $this->expectExceptionMessage(sprintf("The file %s was not found", $filename));

        $pdfBuilder = ZugferdDocumentPdfBuilder::fromPdfFile(self::$document, self::$sourcePdfFilename);
        $pdfBuilder->attachAdditionalFile($filename);
    }

    public function testAttachAdditionalFileMimetypeUnknown(): void
    {
        $filename = dirname(__FILE__) . "/../assets/dummy_attachment_1.dummy";

        $this->expectException(ZugferdUnknownMimetype::class);
        $this->expectExceptionMessage("No mimetype found");

        $pdfBuilder = ZugferdDocumentPdfBuilder::fromPdfFile(self::$document, self::$sourcePdfFilename);
        $pdfBuilder->attachAdditionalFile($filename);
    }

    public function testAttachAdditionalFileInvalidRelationShip(): void
    {
        $filename = dirname(__FILE__) . "/../assets/txt_addattachment_1.txt";

        $pdfBuilder = ZugferdDocumentPdfBuilder::fromPdfFile(self::$document, self::$sourcePdfFilename);
        $pdfBuilder->attachAdditionalFile($filename, "", "Dummy");

        $property = $this->getPrivatePropertyFromClassname(ZugferdDocumentPdfBuilderAbstract::class, "additionalFilesToAttach");

        $this->assertIsArray($property->getValue($pdfBuilder));
        $this->assertIsArray($property->getValue($pdfBuilder)[0]);
        $this->assertEquals(ZugferdDocumentPdfBuilder::AF_RELATIONSHIP_SUPPLEMENT, $property->getValue($pdfBuilder)[0][3]);
    }

    public function testAttachAdditionalFileValidRelationShip(): void
    {
        $filename = dirname(__FILE__) . "/../assets/txt_addattachment_1.txt";

        $pdfBuilder = ZugferdDocumentPdfBuilder::fromPdfFile(self::$document, self::$sourcePdfFilename);
        $pdfBuilder->attachAdditionalFile($filename, "", "Alternative");

        $property = $this->getPrivatePropertyFromClassname(ZugferdDocumentPdfBuilderAbstract::class, "additionalFilesToAttach");

        $this->assertIsArray($property->getValue($pdfBuilder));
        $this->assertIsArray($property->getValue($pdfBuilder)[0]);
        $this->assertEquals(ZugferdDocumentPdfBuilder::AF_RELATIONSHIP_ALTERNATIVE, $property->getValue($pdfBuilder)[0][3]);
    }

    public function testAttachAdditionalFileFinalResult(): void
    {
        $filename = dirname(__FILE__) . "/../assets/txt_addattachment_1.txt";

        $pdfBuilder = ZugferdDocumentPdfBuilder::fromPdfFile(self::$document, self::$sourcePdfFilename);
        $pdfBuilder->attachAdditionalFile($filename, "", "Alternative");

        $property = $this->getPrivatePropertyFromClassname(ZugferdDocumentPdfBuilderAbstract::class, "additionalFilesToAttach");

        $this->assertIsArray($property->getValue($pdfBuilder));
        $this->assertIsArray($property->getValue($pdfBuilder)[0]);
        $this->assertInstanceOf(StreamReader::class, $property->getValue($pdfBuilder)[0][0]);
        $this->assertEquals("txt_addattachment_1.txt", $property->getValue($pdfBuilder)[0][1]);
        $this->assertEquals("txt_addattachment_1.txt", $property->getValue($pdfBuilder)[0][2]);
        $this->assertEquals(ZugferdDocumentPdfBuilder::AF_RELATIONSHIP_ALTERNATIVE, $property->getValue($pdfBuilder)[0][3]);
    }

    public function testAdditionalFilesAreEmbedded(): void
    {
        $pdfBuilder = ZugferdDocumentPdfBuilder::fromPdfFile(self::$document, self::$sourcePdfFilename);
        $pdfBuilder->attachAdditionalFile(dirname(__FILE__) . "/../assets/txt_addattachment_1.txt");
        $pdfBuilder->generateDocument();
        $pdfBuilder->saveDocument(self::$destPdfFilename);

        $pdfParser = new PdfParser();
        $pdfParsed = $pdfParser->parseFile(self::$destPdfFilename);
        $pdfFilespecs = $pdfParsed->getObjectsByType('Filespec');

        $this->assertIsArray($pdfFilespecs);
        $this->assertEquals(2, count($pdfFilespecs));
        $this->assertArrayHasKey("8_0", $pdfFilespecs);
        $this->assertArrayHasKey("10_0", $pdfFilespecs);

        $pdfFilespec = $pdfFilespecs["8_0"];
        $pdfFilespecDetails = $pdfFilespec->getDetails();

        $this->assertIsArray($pdfFilespecDetails);
        $this->assertArrayHasKey("F", $pdfFilespecDetails);
        $this->assertArrayHasKey("Type", $pdfFilespecDetails);
        $this->assertArrayHasKey("UF", $pdfFilespecDetails);
        $this->assertArrayHasKey("AFRelationship", $pdfFilespecDetails);
        $this->assertArrayHasKey("Desc", $pdfFilespecDetails);
        $this->assertArrayHasKey("EF", $pdfFilespecDetails);
        $this->assertEquals("factur-x.xml", $pdfFilespecDetails["F"]);
        $this->assertEquals("Filespec", $pdfFilespecDetails["Type"]);
        $this->assertEquals("factur-x.xml", $pdfFilespecDetails["UF"]);
        $this->assertEquals(ZugferdDocumentPdfBuilder::AF_RELATIONSHIP_DATA, $pdfFilespecDetails["AFRelationship"]);
        $this->assertEquals("Factur-X Invoice", $pdfFilespecDetails["Desc"]);

        $pdfFilespec = $pdfFilespecs["10_0"];
        $pdfFilespecDetails = $pdfFilespec->getDetails();

        $this->assertIsArray($pdfFilespecDetails);
        $this->assertArrayHasKey("F", $pdfFilespecDetails);
        $this->assertArrayHasKey("Type", $pdfFilespecDetails);
        $this->assertArrayHasKey("UF", $pdfFilespecDetails);
        $this->assertArrayHasKey("AFRelationship", $pdfFilespecDetails);
        $this->assertArrayHasKey("Desc", $pdfFilespecDetails);
        $this->assertArrayHasKey("EF", $pdfFilespecDetails);
        $this->assertEquals("txt_addattachment_1.txt", $pdfFilespecDetails["F"]);
        $this->assertEquals("Filespec", $pdfFilespecDetails["Type"]);
        $this->assertEquals("txt_addattachment_1.txt", $pdfFilespecDetails["UF"]);
        $this->assertEquals(ZugferdDocumentPdfBuilder::AF_RELATIONSHIP_SUPPLEMENT, $pdfFilespecDetails["AFRelationship"]);
        $this->assertEquals("txt_addattachment_1.txt", $pdfFilespecDetails["Desc"]);

        $pdfFilespecDetailsEF = $pdfFilespecDetails["EF"];
        $this->assertIsArray($pdfFilespecDetailsEF);
        $this->assertArrayHasKey("F", $pdfFilespecDetailsEF);
        $this->assertArrayHasKey("UF", $pdfFilespecDetailsEF);

        $pdfFilespecDetailsEF_F = $pdfFilespecDetailsEF["F"];
        $this->assertIsArray($pdfFilespecDetailsEF_F);
        $this->assertArrayHasKey("Filter", $pdfFilespecDetailsEF_F);
        $this->assertArrayHasKey("Subtype", $pdfFilespecDetailsEF_F);
        $this->assertArrayHasKey("Type", $pdfFilespecDetailsEF_F);
        $this->assertArrayHasKey("Length", $pdfFilespecDetailsEF_F);
        $this->assertEquals("FlateDecode", $pdfFilespecDetailsEF_F["Filter"]);
        $this->assertEquals("text/plain", $pdfFilespecDetailsEF_F["Subtype"]);
        $this->assertEquals("EmbeddedFile", $pdfFilespecDetailsEF_F["Type"]);
        $this->assertEquals(195, $pdfFilespecDetailsEF_F["Length"]);

        $pdfFilespecDetailsEF_UF = $pdfFilespecDetailsEF["UF"];
        $this->assertIsArray($pdfFilespecDetailsEF_UF);
        $this->assertArrayHasKey("Filter", $pdfFilespecDetailsEF_UF);
        $this->assertArrayHasKey("Subtype", $pdfFilespecDetailsEF_UF);
        $this->assertArrayHasKey("Type", $pdfFilespecDetailsEF_UF);
        $this->assertArrayHasKey("Length", $pdfFilespecDetailsEF_UF);
        $this->assertEquals("FlateDecode", $pdfFilespecDetailsEF_UF["Filter"]);
        $this->assertEquals("text/plain", $pdfFilespecDetailsEF_UF["Subtype"]);
        $this->assertEquals("EmbeddedFile", $pdfFilespecDetailsEF_UF["Type"]);
        $this->assertEquals(195, $pdfFilespecDetailsEF_UF["Length"]);
    }
}
