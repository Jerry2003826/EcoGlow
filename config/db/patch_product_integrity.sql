-- Apply after resolving any existing duplicate ProductId inventory rows or
-- duplicate CartId/ProductId cart rows in the live database.
ALTER TABLE `Inventory`
    ADD CONSTRAINT `uq_inventory_product` UNIQUE (`ProductId`);

ALTER TABLE `Cart_Item`
    ADD CONSTRAINT `uq_cartitem_cart_product` UNIQUE (`CartId`, `ProductId`);