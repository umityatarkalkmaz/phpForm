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

    public function testMethodCheckIsCaseInsensitive(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'post';

        self::assertTrue(Form::isPost());
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

    public function testValidatePostWithNoRequiredFieldsSucceeds(): void
    {
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
