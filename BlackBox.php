<?php declare(strict_types=1);
/**
 * BlackBox
 */

namespace BlackBox;

use InvalidArgumentException;

final class BlackBox
{
    const BALL = '🟡'; // 🟡 ⭕ ● ⬤ ⚫ ⧇ ⚽ ⚾ 🎱 🏐 🏀
    const EMPTY = '⚫'; // ☐ □ ▢ ■ ⬜ ⬛
    const BORD = '⬜';
    const CORNER = '❎'; // ❎

    private array $board = [];

    public function __toString(): string
    {
        $str = '';
        foreach ($this->board as $row) {
            foreach ($row as $cell) {
                $str .= $cell;
            }
            $str .= PHP_EOL;
        }
        $str .= PHP_EOL;

        return $str;
    }

    public function createRandomGame(int $size, int $balls): array
    {
        if ($size < 5) {
            throw new InvalidArgumentException('Invalid board size');
        }
        if ($balls < 1) {
            throw new InvalidArgumentException('Invalid number of balls');
        }
        // This check is not necessary, but it is good to have it.
        // How many is many?
        if ($balls > $size) {
            throw new InvalidArgumentException('Too many balls');
        }

        // Create empty board
        $board = array_fill(0, $size, array_fill(0, $size, self::EMPTY));

        // Randomize balls
        $cnt = 0;
        while ($cnt < $balls) {
            $r = mt_rand(0, $size - 1);
            $c = mt_rand(0, $size - 1);
            if ($board[$r][$c] == self::EMPTY) {
                $board[$r][$c] = self::BALL; // Hide a ball in the box
                $cnt++;
            }
        }
        return $board;
    }

    public function createPlayingBoard(array $blackBox)
    {
        $size = count($blackBox);
        // make a board with 2 more rows and columns
        $this->board = array_fill(0, $size + 2, array_fill(0, $size + 2, self::BORD));
        // copy the black box
        for ($r = 0; $r < $size; $r++) {
            for ($c = 0; $c < $size; $c++) {
                $this->board[$r + 1][$c + 1] = $blackBox[$r][$c];
            }
        }
        // 4 corners
        $this->board[0][0] = self::CORNER;
        $this->board[0][$size + 1] = self::CORNER;
        $this->board[$size + 1][0] = self::CORNER;
        $this->board[$size + 1][$size + 1] = self::CORNER;
    }

    public function sendLaserBeams()
    {
        $board = $this->board;
        $size = count($board);
        // 4 directions
        $directions = [
            [1, 0], // down
            [0, 1], // right
            [-1, 0], // up
            [0, -1], // left
        ];

    }
}

// cli?
if (php_sapi_name() == 'cli') {
    $size = $argv[1] ?? 5;
    $balls = $argv[2] ?? 3;
    $game = new BlackBox();
    $bb = $game->createRandomGame($size, $balls);
    $game->createPlayingBoard($bb);
    $game->sendLaserBeams();
    echo $game;
}
