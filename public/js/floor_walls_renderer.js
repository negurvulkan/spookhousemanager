(function () {
    const DEFAULT_CELL_SIZE = 32;
    const SPRITE_CACHE = new Map();
    const WALL_THICKNESS_RATIO = 0.25;

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

    function normalizeNumber(value, fallback = 0) {
        if (typeof value === 'number' && Number.isFinite(value)) {
            return value;
        }

        if (typeof value === 'string') {
            const trimmed = value.trim();
            if (trimmed !== '') {
                const parsed = Number(trimmed);
                if (Number.isFinite(parsed)) {
                    return parsed;
                }
            }
        }

        return fallback;
    }

    function resolveSpritePath(side) {
        if (!side) {
            return null;
        }

        return side.sprite_path || side.sprite || null;
    }

    function normalizeWallData(wall) {
        if (!wall) {
            return null;
        }

        const start = wall.start || {};
        const end = wall.end || {};

        let startX;
        let startY;
        let endX;
        let endY;
        let orientation = wall.orientation || null;

        if (typeof start.x !== 'undefined' || typeof start.y !== 'undefined') {
            startX = normalizeNumber(start.x);
            startY = normalizeNumber(start.y);
        }

        if (typeof end.x !== 'undefined' || typeof end.y !== 'undefined') {
            endX = normalizeNumber(end.x);
            endY = normalizeNumber(end.y);
        }

        if (typeof startX === 'undefined' || typeof startY === 'undefined' ||
            typeof endX === 'undefined' || typeof endY === 'undefined') {
            const startCol = typeof wall.start_col !== 'undefined' ? wall.start_col : wall.startCol;
            const endCol = typeof wall.end_col !== 'undefined' ? wall.end_col : wall.endCol;
            const startRow = typeof wall.start_row !== 'undefined' ? wall.start_row : wall.startRow;
            const endRow = typeof wall.end_row !== 'undefined' ? wall.end_row : wall.endRow;

            if (typeof startX === 'undefined' && typeof startCol !== 'undefined') {
                startX = colToIndex(startCol);
            }
            if (typeof endX === 'undefined' && typeof endCol !== 'undefined') {
                endX = colToIndex(endCol);
            }
            if (typeof startY === 'undefined' && typeof startRow !== 'undefined') {
                startY = normalizeNumber(startRow);
            }
            if (typeof endY === 'undefined' && typeof endRow !== 'undefined') {
                endY = normalizeNumber(endRow);
            }
        }

        if (typeof startX === 'undefined' || typeof startY === 'undefined' ||
            typeof endX === 'undefined' || typeof endY === 'undefined') {
            return null;
        }

        const isHorizontal = startY === endY;
        const isVertical = startX === endX;

        if (!orientation) {
            if (isHorizontal) {
                orientation = 'horizontal';
            } else if (isVertical) {
                orientation = 'vertical';
            } else {
                orientation = 'unknown';
            }
        }

        if (orientation === 'horizontal' && startX > endX) {
            const tmp = startX;
            startX = endX;
            endX = tmp;
        }

        if (orientation === 'vertical' && startY > endY) {
            const tmp = startY;
            startY = endY;
            endY = tmp;
        }

        return {
            startX,
            startY,
            endX,
            endY,
            orientation,
            sides: wall.sides || {},
        };
    }

    function renderWalls(ctx, data, cellSize, wallThickness, offset) {
        if (!data || !Array.isArray(data.walls)) {
            return;
        }

        data.walls.forEach((rawWall) => {
            const wall = normalizeWallData(rawWall);
            if (!wall) {
                return;
            }

            const { startX, startY, endX, endY, orientation, sides } = wall;

            const startPixelX = startX * cellSize + offset;
            const startPixelY = startY * cellSize + offset;
            const endPixelX = endX * cellSize + offset;
            const endPixelY = endY * cellSize + offset;

            if (orientation === 'horizontal') {
                const width = Math.max(cellSize, Math.abs(endPixelX - startPixelX));
                const topY = startPixelY - wallThickness;
                const bottomY = startPixelY;

                const sideAPath = resolveSpritePath(sides.A);
                const sideBPath = resolveSpritePath(sides.B);

                if (sideAPath) {
                    drawWallSprite(ctx, sideAPath, Math.min(startPixelX, endPixelX), topY, width, wallThickness);
                }

                if (sideBPath) {
                    drawWallSprite(ctx, sideBPath, Math.min(startPixelX, endPixelX), bottomY, width, wallThickness);
                }
            } else if (orientation === 'vertical') {
                const height = Math.max(cellSize, Math.abs(endPixelY - startPixelY));
                const leftX = startPixelX - wallThickness;
                const rightX = startPixelX;

                const sideAPath = resolveSpritePath(sides.A);
                const sideBPath = resolveSpritePath(sides.B);

                if (sideAPath) {
                    drawWallSprite(ctx, sideAPath, leftX, Math.min(startPixelY, endPixelY), wallThickness, height);
                }

                if (sideBPath) {
                    drawWallSprite(ctx, sideBPath, rightX, Math.min(startPixelY, endPixelY), wallThickness, height);
                }
            }
        });
    }

    function computeGridExtents(data) {
        let maxX = 0;
        let maxY = 0;

        if (data && Array.isArray(data.walls)) {
            data.walls.forEach((rawWall) => {
                const wall = normalizeWallData(rawWall);
                if (!wall) {
                    return;
                }

                maxX = Math.max(maxX, wall.startX, wall.endX);
                maxY = Math.max(maxY, wall.startY, wall.endY);
            });
        }

        const maxColumnIndex = Math.ceil(Math.max(0, maxX));
        const maxRowIndex = Math.ceil(Math.max(0, maxY));

        return {
            maxX,
            maxY,
            columns: Math.max(1, maxColumnIndex + 1),
            rows: Math.max(1, maxRowIndex + 1),
        };
    }

    function determineCanvasSize(data, cellSize, offset) {
        const extents = computeGridExtents(data);

        return {
            width: extents.columns * cellSize + offset * 2,
            height: extents.rows * cellSize + offset * 2,
            columns: extents.columns,
            rows: extents.rows,
        };
    }

    function renderGrid(ctx, columns, rows, cellSize, offset) {
        if (!ctx || !Number.isFinite(columns) || !Number.isFinite(rows)) {
            return;
        }

        const totalWidth = columns * cellSize;
        const totalHeight = rows * cellSize;

        ctx.save();

        ctx.fillStyle = 'rgba(15, 23, 42, 0.35)';
        ctx.fillRect(offset, offset, totalWidth, totalHeight);

        ctx.lineWidth = 1;
        ctx.strokeStyle = 'rgba(148, 163, 184, 0.25)';

        for (let col = 0; col <= columns; col += 1) {
            const x = offset + col * cellSize;
            ctx.beginPath();
            ctx.moveTo(x, offset);
            ctx.lineTo(x, offset + totalHeight);
            ctx.stroke();
        }

        for (let row = 0; row <= rows; row += 1) {
            const y = offset + row * cellSize;
            ctx.beginPath();
            ctx.moveTo(offset, y);
            ctx.lineTo(offset + totalWidth, y);
            ctx.stroke();
        }

        ctx.lineWidth = 2;
        ctx.strokeStyle = 'rgba(248, 250, 252, 0.4)';
        ctx.strokeRect(offset, offset, totalWidth, totalHeight);

        ctx.restore();
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
                const wallThickness = Math.max(4, Math.round(cellSize * WALL_THICKNESS_RATIO));
                const offset = Math.max(cellSize, wallThickness * 2);
                const dimensions = determineCanvasSize(data, cellSize, offset);
                canvas.width = dimensions.width;
                canvas.height = dimensions.height;
                ctx.clearRect(0, 0, canvas.width, canvas.height);
                renderGrid(ctx, dimensions.columns, dimensions.rows, cellSize, offset);
                renderWalls(ctx, data, cellSize, wallThickness, offset);
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

    function renderWallsWithDefaults(ctx, data, cellSize = DEFAULT_CELL_SIZE, wallThickness, offset) {
        const resolvedCellSize = Number.isFinite(cellSize) && cellSize > 0 ? cellSize : DEFAULT_CELL_SIZE;
        const resolvedThickness = Number.isFinite(wallThickness) && wallThickness > 0
            ? wallThickness
            : Math.max(4, Math.round(resolvedCellSize * WALL_THICKNESS_RATIO));
        const resolvedOffset = Number.isFinite(offset) && offset > 0
            ? offset
            : Math.max(resolvedCellSize, resolvedThickness * 2);

        const extents = computeGridExtents(data);
        if (ctx && ctx.canvas) {
            ctx.clearRect(0, 0, ctx.canvas.width, ctx.canvas.height);
        }
        renderGrid(ctx, extents.columns, extents.rows, resolvedCellSize, resolvedOffset);
        renderWalls(ctx, data, resolvedCellSize, resolvedThickness, resolvedOffset);
    }

    window.renderWalls = renderWallsWithDefaults;
    window.drawWallSprite = drawWallSprite;
    window.colToIndex = colToIndex;
})();
