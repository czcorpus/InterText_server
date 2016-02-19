USE intertext;
ALTER TABLE versions ADD COLUMN filename TEXT;
ALTER TABLE versions ADD INDEX index_filename (filename(20));
