<?php

declare(strict_types=1);

namespace UmitYatarkalkmaz\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use UmitYatarkalkmaz\Form;

final class FormTest extends TestCase
{
    protected function setUp(): void
    {
        $_GET = [];
        $_POST = [];
        unset($_SERVER['REQUEST_METHOD']);
    }

    protected function tearDown(): void
    {
        $_GET = [];
        $_POST = [];
        unset($_SERVER['REQUEST_METHOD']);
    }

    public function testIsPostAndIsGetFollowTheRequestMethod(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        self::assertTrue(Form::isPost());
        self::assertFalse(Form::isGet());

        $_SERVER['REQUEST_METHOD'] = 'GET';
        self::assertTrue(Form::isGet());
        self::assertFalse(Form::isPost());
    }

    public function testMethodCheckIsCaseSensitive(): void
    {
        // HTTP methods are case-sensitive; 'post' is not the POST method.
        $_SERVER['REQUEST_METHOD'] = 'post';

        self::assertFalse(Form::isPost());

        $_SERVER['REQUEST_METHOD'] = 'Get';

        self::assertFalse(Form::isGet());
    }

    public function testMethodChecksAreFalseWhenTheServerDidNotSetOne(): void
    {
        self::assertFalse(Form::isPost());
        self::assertFalse(Form::isGet());
    }

    public function testFetchTrimsTheValue(): void
    {
        $_POST['username'] = "  Ümit  \n";
        $_GET['page'] = ' 3 ';

        self::assertSame('Ümit', Form::fetchPost('username'));
        self::assertSame('3', Form::fetchGet('page'));
    }

    #[DataProvider('provideInvisibleWhitespace')]
    public function testFetchTrimsInvisibleWhitespaceTheBrowserSends(string $whitespace): void
    {
        $_POST['username'] = $whitespace . 'Ümit' . $whitespace;
        $_GET['page'] = $whitespace;

        self::assertSame('Ümit', Form::fetchPost('username'));
        self::assertSame('', Form::fetchGet('page'));
    }

    #[DataProvider('provideInvisibleWhitespace')]
    public function testAFieldOfNothingButInvisibleWhitespaceIsNotFilledIn(string $whitespace): void
    {
        $_POST = ['email' => 'a@b.test', 'name' => $whitespace];

        self::assertNull(Form::validatePost(['email', 'name']));
        self::assertSame(['name'], Form::findMissingPost(['email', 'name']));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideInvisibleWhitespace(): iterable
    {
        yield 'ascii space' => [' '];
        yield 'tab and newline' => ["\t\n"];
        yield 'no-break space' => ["\u{00A0}"];
        yield 'zero width space' => ["\u{200B}"];
        yield 'zero width non-joiner' => ["\u{200C}"];
        yield 'zero width joiner' => ["\u{200D}"];
        yield 'byte order mark' => ["\u{FEFF}"];
        yield 'mixed' => ["\u{FEFF} \u{00A0}\t"];
    }

    public function testInvalidUtf8StillGetsAsciiTrimming(): void
    {
        $_POST['blob'] = "  \xff\xfe  ";

        self::assertSame("\xff\xfe", Form::fetchPost('blob'));
    }

    public function testAValueHoldingANulByteIsTreatedAsAbsent(): void
    {
        $_POST['file'] = "avatar.png\x00.php";
        $_GET['id'] = "1\x00";

        self::assertNull(Form::fetchPost('file'));
        self::assertSame('none', Form::fetchPost('file', 'none'));
        self::assertNull(Form::fetchGet('id'));
        self::assertNull(Form::validatePost(['file']));
        self::assertSame(['file'], Form::findMissingPost(['file']));
    }

    public function testFetchDoesNotEscapeTheValue(): void
    {
        $_POST['bio'] = 'Tom & Jerry <3';

        self::assertSame('Tom & Jerry <3', Form::fetchPost('bio'));
    }

    public function testAbsentFieldReturnsTheDefault(): void
    {
        self::assertNull(Form::fetchPost('absent'));
        self::assertNull(Form::fetchGet('absent'));
        self::assertSame('fallback', Form::fetchPost('absent', 'fallback'));
        self::assertSame('fallback', Form::fetchGet('absent', 'fallback'));
    }

    public function testArrayInputIsTreatedAsAbsentRatherThanCrashing(): void
    {
        $_GET['id'] = ['1', '2'];
        $_POST['tags'] = ['a'];

        self::assertNull(Form::fetchGet('id'));
        self::assertSame('none', Form::fetchPost('tags', 'none'));
    }

    public function testZeroIsAValueNotAnAbsence(): void
    {
        $_POST['quantity'] = '0';

        self::assertSame('0', Form::fetchPost('quantity'));
        self::assertSame(['quantity' => '0'], Form::validatePost(['quantity']));
        self::assertSame([], Form::findMissingPost(['quantity']));
    }

    public function testValidatePostReturnsOnlyTheRequestedFields(): void
    {
        $_POST = ['email' => ' a@b.test ', 'name' => 'Ümit', 'extra' => 'ignored'];

        self::assertSame(
            ['email' => 'a@b.test', 'name' => 'Ümit'],
            Form::validatePost(['email', 'name']),
        );
    }

    /**
     * @param array<string, mixed> $post
     */
    #[DataProvider('provideIncompletePosts')]
    public function testValidatePostReportsFailureAsNull(array $post): void
    {
        $_POST = $post;

        self::assertNull(Form::validatePost(['email', 'name']));
    }

    /**
     * @return iterable<string, array{array<string, mixed>}>
     */
    public static function provideIncompletePosts(): iterable
    {
        yield 'field missing' => [['email' => 'a@b.test']];
        yield 'field empty' => [['email' => 'a@b.test', 'name' => '']];
        yield 'field only whitespace' => [['email' => 'a@b.test', 'name' => "   \t"]];
        yield 'field is an array' => [['email' => 'a@b.test', 'name' => ['Ümit']]];
        yield 'nothing submitted' => [[]];
    }

    public function testFindMissingPostNamesEveryUnusableField(): void
    {
        $_POST = ['email' => '', 'name' => 'Ümit', 'phone' => '  '];

        self::assertSame(['email', 'phone'], Form::findMissingPost(['email', 'name', 'phone']));
    }

    public function testValidatePostWithNoRequiredFieldsSucceedsWithAFalsyResult(): void
    {
        // Success is [], which is falsy: only === null reports failure.
        self::assertSame([], Form::validatePost([]));
    }

    #[DataProvider('provideEscapes')]
    public function testEscapeHtmlNeutralisesMarkup(string $raw, string $expected): void
    {
        self::assertSame($expected, Form::escapeHtml($raw));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideEscapes(): iterable
    {
        yield 'script tag' => ['<script>alert(1)</script>', '&lt;script&gt;alert(1)&lt;/script&gt;'];
        yield 'double quote' => ['" onmouseover="x', '&quot; onmouseover=&quot;x'];
        yield 'single quote' => ["' onload='x", '&#039; onload=&#039;x'];
        yield 'ampersand' => ['Tom & Jerry', 'Tom &amp; Jerry'];
        yield 'unicode is left alone' => ['Şeker Ağacı', 'Şeker Ağacı'];
        yield 'empty string' => ['', ''];
    }

    public function testEscapingIsSomethingTheCallerDoesOnce(): void
    {
        $_POST['bio'] = 'Tom & Jerry';

        $stored = Form::fetchPost('bio');

        self::assertNotNull($stored);
        self::assertSame('Tom & Jerry', $stored);
        self::assertSame('Tom &amp; Jerry', Form::escapeHtml($stored));
    }
}
