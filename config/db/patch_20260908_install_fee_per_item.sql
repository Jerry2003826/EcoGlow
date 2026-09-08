-- Per-item installation fee is $80. Existing paid carts keep stored totals.
ALTER TABLE `Shopping_Cart`
    MODIFY `InstallFee` DECIMAL(10,2) NOT NULL DEFAULT 80.00;
