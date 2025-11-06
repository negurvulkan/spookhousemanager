(function () {
    const DEFAULT_CELL_SIZE = 32;
    const SPRITE_CACHE = new Map();

    function colToIndex(col) {
        if (!col) {
            return 0;
        }

        const label = col.toUpperCase();
        let index = 0;
        for (let i = 0; i < label.length; i += 1) {
            const charCode = label.charCodeAt(i) - 64; // 'A' => 1
            if (charCode < 1 || charCode > 26) {
                continue;
            }
            index = index * 26 + charCode;
        }

        return Math.max(0, index - 1);
    }

    function drawWallSprite(ctx, spritePath, x, y, width, height) {
        if (!spritePath) {
            return;
        }

        if (SPRITE_CACHE.has(spritePath)) {
            const cached = SPRITE_CACHE.get(spritePath);
            if (cached.complete) {
                ctx.drawImage(cached, x, y, width, height);
            } else {
                cached.addEventListener('load', () => {
                    ctx.drawImage(cached, x, y, width, height);
                }, { once: true });
            }
            return;
        }

        const image = new Image();
        image.addEventListener('load', () => {
            ctx.drawImage(image, x, y, width, height);
        });
        image.src = spritePath;
        SPRITE_CACHE.set(spritePath, image);
    }

    function renderWalls(ctx, data, cellSize) {
        if (!data || !Array.isArray(data.walls)) {
            return;
        }

        data.walls.forEach((wall) => {
            const sx = colToIndex(wall.start_col) * cellSize;
            const sy = wall.start_row * cellSize;
            const ex = colToIndex(wall.end_col) * cellSize;
            const ey = wall.end_row * cellSize;
            const isHorizontal = (sy === ey);

            if (isHorizontal) {
                if (wall.sides.A && wall.sides.A.sprite) {
                    drawWallSprite(ctx, wall.sides.A.sprite, sx, sy - 6, ex - sx || cellSize, 12);
                }
                if (wall.sides.B && wall.sides.B.sprite) {
                    drawWallSprite(ctx, wall.sides.B.sprite, sx, sy + (cellSize - 6), ex - sx || cellSize, 12);
                }
            } else {
                if (wall.sides.A && wall.sides.A.sprite) {
                    drawWallSprite(ctx, wall.sides.A.sprite, sx - 6, sy, 12, ey - sy || cellSize);
                }
                if (wall.sides.B && wall.sides.B.sprite) {
                    drawWallSprite(ctx, wall.sides.B.sprite, sx + (cellSize - 6), sy, 12, ey - sy || cellSize);
                }
            }
        });
    }

    function determineCanvasSize(data, cellSize) {
        let maxCol = 0;
        let maxRow = 0;

        if (data && Array.isArray(data.walls)) {
            data.walls.forEach((wall) => {
                maxCol = Math.max(maxCol, colToIndex(wall.start_col), colToIndex(wall.end_col));
                maxRow = Math.max(maxRow, wall.start_row, wall.end_row);
            });
        }

        return {
            width: (maxCol + 2) * cellSize,
            height: (maxRow + 2) * cellSize,
        };
    }

    function fetchAndRenderWalls(canvas) {
        const ctx = canvas.getContext('2d');
        const floorId = canvas.dataset.floorId;
        const apiEndpoint = canvas.dataset.apiEndpoint;
        const cellSize = Number(canvas.dataset.cellSize) || DEFAULT_CELL_SIZE;

        if (!floorId || !apiEndpoint) {
            return;
        }

        fetch(`${apiEndpoint}?floor_id=${encodeURIComponent(floorId)}`, { credentials: 'same-origin' })
            .then((response) => response.json())
            .then((data) => {
                const dimensions = determineCanvasSize(data, cellSize);
                canvas.width = dimensions.width;
                canvas.height = dimensions.height;
                ctx.clearRect(0, 0, canvas.width, canvas.height);
                renderWalls(ctx, data, cellSize);
            })
            .catch(() => {
                // Example script: intentionally minimal error handling
                ctx.fillStyle = '#ff4d4f';
                ctx.fillRect(0, 0, canvas.width, canvas.height);
            });
    }

    document.addEventListener('DOMContentLoaded', () => {
        const canvas = document.getElementById('houseCanvas');
        if (!canvas) {
            return;
        }

        fetchAndRenderWalls(canvas);
    });

    window.renderWalls = renderWalls;
    window.drawWallSprite = drawWallSprite;
    window.colToIndex = colToIndex;
})();
