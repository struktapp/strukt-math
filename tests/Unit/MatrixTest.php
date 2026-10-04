<?php

/** Coverage for matrix arithmetic, vector products, and norms. */

use Strukt\Matrix;
use Strukt\Number;
use Strukt\Raise;

test('dot multiplies matching entries and adds the products', function (): void {
    expect(Matrix::dot([1, 2], [3, 4])->yield())->toBe(11)
        ->and(Matrix::dot([1, 0], [0, 1])->yield())->toBe(0)
        ->and(Matrix::dot([2], [3])->yield())->toBe(6)
        ->and(Matrix::dot([], [])->yield())->toBe(0);
});

test('dot accepts a mix of raw numbers and Number objects', function (): void {
    expect(Matrix::dot([number(1), 2], [3, number(4)])->yield())->toBe(11)
        ->and(Matrix::dot([1, 2], [3, 4])->type())->toBe('integer');
});

test('dot ignores keys and pairs entries by position', function (): void {
    expect(Matrix::dot(['a' => 1, 'b' => 2], ['x' => 3, 'y' => 4])->yield())->toBe(11);
});

test('dot rejects vectors of different lengths', function (): void {
    Matrix::dot([1, 2], [1]);
})->throws(InvalidArgumentException::class, 'Vectors must have the same length, got [2] and [1].');

test('cosine similarity is one for parallel vectors', function (): void {
    expect(Matrix::cosineSimilarity([1, 2, 3], [1, 2, 3])->yield())->toEqualWithDelta(1.0, 1e-12)
        ->and(Matrix::cosineSimilarity([1, 2], [2, 4])->yield())->toEqualWithDelta(1.0, 1e-12);
});

test('cosine similarity is zero for orthogonal and minus one for opposite vectors', function (): void {
    expect(Matrix::cosineSimilarity([1, 0], [0, 1])->yield())->toEqualWithDelta(0.0, 1e-12)
        ->and(Matrix::cosineSimilarity([1, 0], [-1, 0])->yield())->toEqualWithDelta(-1.0, 1e-12);
});

test('cosine similarity reproduces a known term frequency result', function (): void {
    expect(Matrix::cosineSimilarity([1, 1, 0], [1, 1, 1])->yield())
        ->toEqualWithDelta(0.816496580927726, 1e-12);
});

test('cosine similarity rejects a zero vector because its direction is undefined', function (): void {
    Matrix::cosineSimilarity([0, 0], [1, 1]);
})->throws(DivisionByZeroError::class);

test('cosine similarity rejects vectors of different lengths', function (): void {
    Matrix::cosineSimilarity([1, 2], [1]);
})->throws(InvalidArgumentException::class);

test('matrices add and subtract element by element', function (): void {
    $left = Matrix::create([[1, 2], [3, 4]]);

    expect($left->add(Matrix::create([[1, 1], [1, 1]]))->yield())->toBe([[2, 3], [4, 5]])
        ->and($left->subtract(Matrix::create([[1, 1], [1, 1]]))->yield())->toBe([[0, 1], [2, 3]]);
});

test('adding and subtracting leaves the operand untouched', function (): void {
    $left = Matrix::create([[1, 2]]);
    $right = Matrix::create([[3, 4]]);

    $left->add($right);

    expect($left->yield())->toBe([[1, 2]])
        ->and($right->yield())->toBe([[3, 4]]);
});

test('add and subtract require matching shapes', function (): void {
    Matrix::create([[1, 2]])->add(Matrix::create([[1, 2, 3]]));
})->throws(Raise::class);

test('subtract requires matching shapes', function (): void {
    Matrix::create([[1, 2], [3, 4]])->subtract(Matrix::create([[1, 2]]));
})->throws(Raise::class);

test('scale multiplies every entry by a factor', function (): void {
    $matrix = Matrix::create([[1, 2], [3, 4]]);

    expect($matrix->scale(2)->yield())->toBe([[2, 4], [6, 8]])
        ->and($matrix->scale(0.5)->yield())->toBe([[0.5, 1.0], [1.5, 2.0]])
        ->and($matrix->scale(number(3))->yield())->toBe([[3, 6], [9, 12]])
        ->and($matrix->scale(0)->yield())->toBe([[0, 0], [0, 0]]);
});

test('scale leaves the operand untouched', function (): void {
    $matrix = Matrix::create([[1, 2]]);
    $matrix->scale(5);

    expect($matrix->yield())->toBe([[1, 2]]);
});

test('the euclidean norm is the square root of the sum of squares', function (): void {
    expect(Matrix::create([[3, 4]])->norm()->yield())->toEqualWithDelta(5.0, 1e-12)
        ->and(Matrix::create([[3, 4]])->norm(2)->yield())->toEqualWithDelta(5.0, 1e-12)
        ->and(Matrix::create([[1, 2], [3, 4]])->norm()->yield())->toEqualWithDelta(sqrt(30), 1e-12)
        ->and(Matrix::create([[0, 0]])->norm()->yield())->toBe(0.0);
});

test('the one norm is the sum of absolute values', function (): void {
    expect(Matrix::create([[1, -2], [3, -4]])->norm(1)->yield())->toBe(10)
        ->and(Matrix::create([[-3, -4]])->norm(2)->yield())->toEqualWithDelta(5.0, 1e-12);
});

test('the norm is returned as a Number', function (): void {
    expect(Matrix::create([[1, 2]])->norm())->toBeInstanceOf(Number::class);
});

test('the norm rejects an unsupported order', function (): void {
    Matrix::create([[1, 2]])->norm(3);
})->throws(InvalidArgumentException::class, 'Only the 1-norm and 2-norm are supported, got [3].');

test('the matrix helper exposes the new operations', function (): void {
    $helper = matrix([[1, 2], [3, 4]]);

    expect($helper->dot([1, 2], [3, 4])->yield())->toBe(11)
        ->and($helper->cosineSimilarity([1, 0], [1, 0])->yield())->toEqualWithDelta(1.0, 1e-12)
        ->and($helper->add([[1, 1], [1, 1]])->yield())->toBe([[2, 3], [4, 5]])
        ->and($helper->subtract([[1, 1], [1, 1]])->yield())->toBe([[0, 1], [2, 3]])
        ->and($helper->scale(2)->yield())->toBe([[2, 4], [6, 8]])
        ->and($helper->norm()->yield())->toEqualWithDelta(sqrt(30), 1e-12);
});

test('the matrix helper returns null when no base matrix was supplied', function (): void {
    $helper = matrix(null);

    expect($helper->add([[1]]))->toBeNull()
        ->and($helper->subtract([[1]]))->toBeNull()
        ->and($helper->scale(2))->toBeNull()
        ->and($helper->norm())->toBeNull()
        ->and($helper->yield())->toBeNull();
});
