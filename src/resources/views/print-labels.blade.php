<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Labels</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        @page {
            size: Letter;
            margin: 0.5in;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
        }

        .labels-container {
            display: flex;
            flex-wrap: wrap;
            gap: 0.25in;
        }

        .label {
            width: {{ $labelWidth }};
            height: {{ $labelHeight }};
            border: 1px solid #ddd;
            padding: 0.1in;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            page-break-inside: avoid;
            text-align: center;
        }

        .label-tag {
            font-weight: bold;
            font-size: 14px;
            margin-bottom: 0.02in;
        }

        .label-barcode {
            margin: 0.02in 0;
        }

        .label-barcode svg {
            max-width: 100%;
            height: auto;
        }

        .label-info {
            font-size: 10px;
            color: #666;
            margin-top: 0.02in;
        }

        @media print {
            .no-print {
                display: none;
            }

            .label {
                border: none;
            }
        }

        .print-controls {
            position: fixed;
            top: 20px;
            right: 20px;
            background: white;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            z-index: 1000;
        }

        .print-controls button {
            padding: 10px 20px;
            margin: 5px;
            cursor: pointer;
            background: #4CAF50;
            color: white;
            border: none;
            border-radius: 3px;
            font-size: 14px;
        }

        .print-controls button:hover {
            background: #45a049;
        }
    </style>
</head>
<body>
    <div class="print-controls no-print">
        <button onclick="window.print()">Print Labels</button>
        <button onclick="window.close()">Close</button>
    </div>

    <div class="labels-container">
        @foreach($tags as $tag)
            <div class="label">
                <div class="label-tag">{{ $tag->value }}</div>
                <div class="label-barcode">
                    {!! $tag->generateBarcodeSVG(null, 2, 40) !!}
                </div>
                <div class="label-info">
                    @if($tag->taggable)
                        {{ $tag->taggable->getTagLabel() }}
                    @else
                        {{ class_basename($tag->taggable_type) }} #{{ $tag->taggable_id }}
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</body>
</html>
