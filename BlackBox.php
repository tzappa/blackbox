<?php declare(strict_types=1);
/**
 * BlackBox
 */

namespace BlackBox;

use InvalidArgumentException;

final class BlackBox
{
    const BALL = '🟡';    // Hidden ball 🟡 ⭕ ● ⚫ 🎱
    const EMPTY = '⬛';   // Black box ☐ □ ▢ ■ ⚫ ⬜ ⬛
    const PORT = '⬜';    // Entry port for laser beam
    const CORNER = '  ';  // Corner of the board ❎ ⬛
    const HIT = '🟥';     // Hit
    const REFLECT = '🟨'; // Reflection

    public function toString(array $board): string
    {
        $str = '';
        foreach ($board as $row) {
            foreach ($row as $cell) {
                $str .= $cell;
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

        try {
            // start a laser beam from each side
            for ($r = 1; $r <= $size; $r++) {
                if ($board[0][$r] == self::PORT) {
                    $board = $this->go($board, 0, $r, 1, 0);
                }
                if ($board[$r][0] == self::PORT) {
                    $board = $this->go($board, $r, 0, 0, 1);
                }
                if ($board[$size + 1][$r] == self::PORT) {
                    $board = $this->go($board, $size + 1, $r, -1, 0);
                }
                if ($board[$r][$size + 1] == self::PORT) {
                    $board = $this->go($board, $r, $size + 1, 0, -1);
                }
            }
        } catch (\Exception $e) { // On Infinite loop
            // recreate the board and try again
            return $this->createPlayingBoard($size, $this->countBalls($board));
        }

        return $board;
    }

    /**
     * @param array $board - Playing board
     * @param integer $row - Starting position (row) of the laser beam
     * @param integer $col - Starting position (column) of the laser beam
     * @param integer $dr - verticcal direction (row) of the laser beam 1 = down, -1 = up
     * @param integer $dc - horizontal direction (column) of the laser beam 1 = right, -1 = left
     */
    private function go(array $board, int $row, int $col, int $dr, int $dc)
    {
        static $num = 0; // The deflection number

        $res = false;
        $r = $row; // current row
        $c = $col; // current column
        $size = count($board) - 2;

        $itt = 0;
        while ($res === false) {
            $itt++;
            // Exit
            if (($r + $dr <= 0) || ($r + $dr >= $size + 1) || ($c + $dc <= 0) || ($c + $dc >= $size + 1)) {
                // From the same point
                if (($r + $dr == $row) && ($c + $dc == $col)) {
                    $res = self::REFLECT;
                } elseif (($itt > 1) && ($r == $row) && ($c == $col)) {
                    $res = self::REFLECT;
                } else {
                    // from somewhere else
                    $num = $num + 1;
                    $res = str_pad((string) $num, 2, '*', STR_PAD_LEFT);
                    if (@$board[$r + $dr][$c + $dc] == self::PORT) {
                        $board[$r + $dr][$c + $dc] = $res;
                    }
                }
            } elseif (@$board[$r + $dr][$c + $dc] == self::BALL) {
                // Hit
                $res = self::HIT;
            } elseif (($dr == 1) || ($dr == -1)) {
                // Deflection - Change direction
                // Vertical
                // from both sides
                if ((@$board[$r + $dr][$c - 1] == self::BALL) && (@$board[$r + $dr][$c + 1] == self::BALL)) {
                    if (($r == 0) || ($r == $size + 1)) {
                        $res = self::REFLECT;
                    } else {
                        // go back
                        $dr = -1 * $dr;
                    }
                } elseif (@$board[$r + $dr][$c - 1] == self::BALL) {
                    // ball from left
                    if (($r == 0) || ($r == $size + 1)) {
                        $res = self::REFLECT;
                    } else {
                        // change direction to right
                        $dr = 0;
                        $dc = 1;
                    }
                } elseif (@$board[$r + $dr][$c + 1] == self::BALL) {
                    // ball from left
                    if (($r == 0) || ($r == $size + 1)) {
                        $res = self::REFLECT;
                    } else {
                        // change direction to right
                        $dr = 0;
                        $dc = -1;
                    }
                }
            } elseif (($dc == 1) || ($dc == -1)) {
                // Horizontal
                // from both sides
                if ((@$board[$r - 1][$c + $dc] == self::BALL) && (@$board[$r + 1][$c + $dc] == self::BALL)) {
                    if (($c == 0) || ($c == $size + 1)) {
                        $res = self::REFLECT;
                    } else {
                        // go back
                        $dc = -1 * $dc;
                    }
                } elseif (@$board[$r + 1][$c + $dc] == self::BALL) {
                    // ball from left
                    if (($c == 0) || ($c == $size + 1)) {
                        $res = self::REFLECT;
                    } else {
                        // change direction to up
                        $dc = 0;
                        $dr = -1;
                    }
                } elseif (@$board[$r - 1][$c + $dc] == self::BALL) {
                    // ball from right
                    if (($c == 0) || ($c == $size + 1)) {
                        $res = self::REFLECT;
                    } else {
                        // change direction to down
                        $dc = 0;
                        $dr = 1;
                    }
                }
            } elseif ($itt > 100) {
                throw new \Exception('too many iterations');
            }
            if ($res) {
                $board[$row][$col] = $res;
                if (($r + $dr < 0) || ($c + $dc < 0) || ($r + $dr > $size + 1) || ($c + $dc > $size + 1)) {
                    if (($row != $r + $dr) && ($col != $c + $dc)) {
                        $board[$r][$c] = $res;
                    }
                }
            } else {
                $c = $c + $dc;
                $r = $r + $dr;
            }
        }
        return $board;
    }

    public function countBalls(array $board): int
    {
        $balls = 0;
        foreach ($board as $row) {
            foreach ($row as $cell) {
                if ($cell == self::BALL) {
                    $balls++;
                }
            }
        }

        return $balls;
    }
}

if (php_sapi_name() == 'cli') {
    $size = (int) ($argv[1] ?? 5);
    $balls = (int) ($argv[2] ?? 3);
    $game = new BlackBox();
    $board = $game->createPlayingBoard($size);
    $board = $game->addRandomBalls($board, $balls);
    $board = $game->sendLaserBeams($board);
    echo $game->toString($board);
}
