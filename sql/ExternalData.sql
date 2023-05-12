CREATE TABLE /*_*/ed_url_cache (
  id SERIAL PRIMARY KEY,
  url varchar(255) NOT NULL UNIQUE,
  post_vars text,
  req_time int NOT NULL,
  result text
) /*$wgDBTableOptions*/;
