DROP TABLE IF EXISTS urls;
DROP TABLE IF EXISTS checks;

CREATE TABLE urls (
                      id bigint PRIMARY KEY GENERATED ALWAYS AS IDENTITY,
                      name varchar(255),
                      created_at timestamp
);

CREATE TABLE checks (
                        id bigint PRIMARY KEY GENERATED ALWAYS AS IDENTITY,
                        url_id bigint REFERENCES urls (id),
                        status_code smallint,
                        h1 varchar(255),
                        title varchar(255),
                        description text,
                        created_at timestamp
);