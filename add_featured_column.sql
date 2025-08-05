-- Add featured column to productos table
ALTER TABLE productos ADD COLUMN destacado TINYINT(1) DEFAULT 0;

-- Mark some products as featured (example)
UPDATE productos SET destacado = 1 WHERE id IN (1, 2, 3, 4, 5, 6);
