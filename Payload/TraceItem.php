<?php

namespace Rollbar\Symfony\RollbarBundle\Payload;

use JetBrains\PhpStorm\ArrayShape;

class TraceItem
{
    #[ArrayShape(['exception' => "array", 'frames' => "array"])]
    public function __invoke(\Throwable $throwable): array
    {
        $frames = [];

        foreach ($throwable->getTrace() as $row) {
            // prepare initial frame
            $frame = [
                'filename'   => empty($row['file']) ? null : $row['file'],
                'lineno'     => empty($row['line']) ? null : $row['line'],
                'class_name' => empty($row['class']) ? null : $row['class'],
                'args'       => empty($row['args']) ? [] : unserialize(serialize($row['args'])),
            ];

            // convert vars to types
            foreach ($frame['args'] as $key => $item) {
                $frame['args'][$key] = gettype($item);
            }

            // build method
            $method          = empty($row['function']) ? null : $row['function'];
            $call            = empty($row['type']) ? '::' : $row['type'];
            $frame['method'] = $frame['class_name'] . $call . $method . '()';

            $frames[] = $frame;
        }

        return [
            'exception' => [
                'class'   => get_class($throwable),
                'message' => implode(' ', [
                    "'\\" . get_class($throwable) . "'",
                    'with message',
                    "'" . $throwable->getMessage() . "'",
                    'occurred in file',
                    "'" . $throwable->getFile() . "'",
                    'line',
                    "'" . $throwable->getLine() . "'",
                    'with code',
                    "'" . $throwable->getCode() . "'",
                ]),
            ],
            'frames'    => $frames,
        ];
    }
}
