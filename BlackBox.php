<?php declare(strict_types=1);
/**
 * Black Box game generator
 * @see https://en.wikipedia.org/wiki/Black_Box_(game)
 *
 * @author Plamen Popov <tzappa [at] gmail.com>
 * @license GPL v3
 * @source https://github.com/tzappa/blackbox
 */

namespace BlackBox;

use InvalidArgumentException;

final class BlackBox
{
    const BALL = 'b';    // Hidden ball
    const EMPTY = 'O';   // Black box
    const PORT = 'P';    // Entry port for laser beam
    const CORNER = '';   // Corner of the board
    const HIT = 'H';     // Hit
    const REFLECT = 'R'; // Reflection

    private array $board;

    public function __construct(int $size = 6, int $balls = 3)
    {
        $board = $this->createPlayingBoard($size);
        $board = $this->addRandomBalls($board, $balls);
        $board = $this->sendLaserBeams($board);
        $this->board = $board;
    }

    public function getBoard(): array
    {
        return $this->board;
    }

    public function __toString(): string
    {
        $conv = [
            self::BALL => '🟡',
            self::EMPTY => '⬛',
            self::PORT => '⬜',
            self::CORNER => '  ', //  ❎ ⬛
            self::HIT => '🟥',
            self::REFLECT => '🟨',
        ];
        $str = '';
        foreach ($this->board as $row) {
            foreach ($row as $cell) {
                if (is_int($cell)) {
                    $str .= str_pad((string) $cell, 2, ' ', STR_PAD_LEFT);
                } else {
                    $str .= $conv[$cell];
                }
            }
            $str .= PHP_EOL;
        }
        $str .= PHP_EOL;

        return $str;
    }

    public function createPlayingBoard(int $size): array
    {
        if ($size < 5) {
            throw new InvalidArgumentException('Board too small');
        }

        if ($size > 20) {
            throw new InvalidArgumentException('Board too big');
        }

        // make a board with 2 more rows and columns
        $board = array_fill(0, $size + 2, array_fill(0, $size + 2, self::PORT));
        // fill the board with empty cells
        for ($r = 0; $r < $size; $r++) {
            for ($c = 0; $c < $size; $c++) {
                $board[$r + 1][$c + 1] = self::EMPTY;
            }
        }
        // 4 corners
        $board[0][0] = self::CORNER;
        $board[0][$size + 1] = self::CORNER;
        $board[$size + 1][0] = self::CORNER;
        $board[$size + 1][$size + 1] = self::CORNER;

        return $board;
    }

    public function addRandomBalls(array $board, int $balls): array
    {
        if ($balls < 1) {
            throw new InvalidArgumentException('Invalid number of balls');
        }
        $size = count($board) - 2;
        // This check is not necessary, but it is good to have it.
        // How many is many?
        if ($balls > $size) {
            throw new InvalidArgumentException('Too many balls');
        }

        // Randomize balls
        $cnt = 0;
        while ($cnt < $balls) {
            $r = mt_rand(1, $size);
            $c = mt_rand(1, $size);
            if ($board[$r][$c] == self::EMPTY) {
                $board[$r][$c] = self::BALL; // Hide a ball in the box
                $cnt++;
            }
        }

        return $board;
    }

    public function sendLaserBeams(array $board): array
    {
        $size = count($board) - 2;

        // start a laser beam from each side
        for ($i = 1; $i <= $size; $i++) {
            if ($board[0][$i] == self::PORT) {
                $board = $this->shoot($board, 0, $i, 1, 0);
            }
            if ($board[$i][0] == self::PORT) {
                $board = $this->shoot($board, $i, 0, 0, 1);
            }
            if ($board[$size + 1][$i] == self::PORT) {
                $board = $this->shoot($board, $size + 1, $i, -1, 0);
            }
            if ($board[$i][$size + 1] == self::PORT) {
                $board = $this->shoot($board, $i, $size + 1, 0, -1);
            }
        }

        return $board;
    }

    private function shoot(array $board, int $row, int $col, int $dr, int $dc): array
    {
        static $num = 0; // The deflection number

        // check for imidiate hit
        if ($board[$row + $dr][$col + $dc] == self::BALL) {
            $board[$row][$col] = self::HIT;
            return $board;
        }
        // check imidiate reflection
        if ($dr == 0) {
            // horizontal
            if (($board[$row + 1][$col + $dc] == self::BALL) || ($board[$row - 1][$col + $dc] == self::BALL)) {
                $board[$row][$col] = self::REFLECT;
                return $board;
            }
        } else {
            // vertical
            if (($board[$row + $dr][$col + 1] == self::BALL) || ($board[$row + $dr][$col - 1] == self::BALL)) {
                $board[$row][$col] = self::REFLECT;
                return $board;
            }
        }

        // move the laser beam
        $r = $row; // current row
        $c = $col; // current column
        $size = count($board) - 2;
        while (true) {
            $r = $r + $dr; // current row
            $c = $c + $dc; // current column

            // check for hit
            if ($board[$r][$c] == self::BALL) {
                $board[$row][$col] = self::HIT;
                return $board;
            }

            // check for exit
            if (($r == 0) || ($c == 0) || ($r == $size + 1) || ($c == $size + 1)) {
                // check the port is the same as the starting point
                if (($r == $row) && ($c == $col)) {
                    $board[$row][$col] = self::REFLECT;
                    return $board;
                } else {
                    $num = $num + 1;
                    $board[$row][$col] = $num;
                    $board[$r][$c] = $num;
                    return $board;
                }
                return $board;
            }

            // check for reflection by 1 or 2 ball(s)
            if ($dr == 0) {
                // horizontal
                if (($board[$r + 1][$c] == self::BALL) && ($board[$r - 1][$c] == self::BALL)) {
                    $board[$row][$col] = self::REFLECT;
                    return $board;
                }
                if ($board[$r + 1][$c] == self::BALL) {
                    $dr = -1;
                    $c = $c - $dc;
                    $dc = 0;
                } elseif ($board[$r - 1][$c] == self::BALL) {
                    $dr = 1;
                    $c = $c - $dc;
                    $dc = 0;
                }
            } else {
                // vertical
                if (($board[$r][$c + 1] == self::BALL) && ($board[$r][$c - 1] == self::BALL)) {
                    $board[$row][$col] = self::REFLECT;
                    return $board;
                }
                if ($board[$r][$c + 1] == self::BALL) {
                    $r = $r - $dr;
                    $dr = 0;
                    $dc = -1;
                } elseif ($board[$r][$c - 1] == self::BALL) {
                    $r = $r - $dr;
                    $dr = 0;
                    $dc = 1;
                }
            }
        }
    }
}

if (php_sapi_name() == 'cli') {
    $size = (int) ($argv[1] ?? 6);
    $balls = (int) ($argv[2] ?? 3);
    $bb = new BlackBox($size, $balls);
    echo $bb;
}
