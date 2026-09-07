-- config/db/patch_20260907_walkthrough_fixes.sql

UPDATE Product
SET CategoryId = 2
WHERE ProductName = 'Ashby Twin Sconce Brass';

UPDATE Orders o
INNER JOIN Payment p
    ON p.OrderId = o.OrderId
SET o.Status = 'paid'
WHERE o.Status = 'pending';
