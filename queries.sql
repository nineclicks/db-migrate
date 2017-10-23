--name: insert-location!
INSERT INTO location (
  cid,
  name,
  street_address,
  city,
  state,
  zip,
  country,
  address_type,
  non_us_street_address,
  lat,
  lng,
  date_created)
VALUES (?,?,?,?,?,?,?,?,?,?,?,?);

--name: get-carmax-id
SELECT id FROM customer WHERE name = 'Carmax';

--name: get-location-by-cid
SELECT * FROM location WHERE cid = ?;

--name: get-vehicle-count
SELECT count(*) as count FROM vehicle;

--name: get-orders
SELECT * FROM `order`;

--name: get-vehicles-by-order-id
SELECT * FROM vehicle WHERE order_id = ?;

--name: get-location-by-id
SELECT * FROM location where id = ? limit 1;

--name: get-locations
SELECT * FROM location;

--name: add-order!
INSERT INTO "order" (
  pickup_location_id,
  dropoff_location_id,
  customer_id,
  fuel_surcharge_amt,
  fuel_surcharge_percent,
  price_per_load,
  price_per_unit,
  additional_charge,
  additional_charge_desc,
  important,
  cod,
  cop,
  move_type,
  eta,
  date_created,
  date_deactivated)
VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?);

--name: add-vehicle!
INSERT INTO "vehicle" (
  order_id,
  year,
  make,
  model,
  vin,
  type,
  classification,
  po_number,
  move_id,
  curb_weight,
  doors,
  move_reason,
  important,
  promise_date,
  date_created,
  date_cancelled)
VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
RETURNING id;

--name: add-transfer!
INSERT INTO "transfer" (
  vehicle_id
) VALUES (?);

--name: get-drivers
SELECT * FROM driver;

--name: add-driver!
INSERT INTO driver (
username,
name,
password,
mobile_number,
alt_number,
home_number,
fax_number,
email,
type,
is_cd,
active,
accepts_txt_messaging,
notes,
dont_use_reason,
start_date,
load_capacity,
truck_registration_date,
license_expiration_date,
medical_expiration_date
) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?);

--name: get-bols
SELECT * FROM bol;

--name: add-bol!
INSERT INTO bol (
  driver_id,
  shipment_id,
  distance,
  pickup_location_id,
  dropoff_location_id,
  pickup_date,
  dropoff_date,
  date_created,
  date_deactivated
) VALUES (
  (SELECT id FROM driver WHERE username = ?),
  ?,
  ?,
  (SELECT id FROM location WHERE name = ? AND street_address = ? AND city = ?),
  (SELECT id FROM location WHERE name = ? AND street_address = ? AND city = ?),
  ?,
  ?,
  ?,
  ?
) RETURNING *;

--name: get-vehicles-by-bol-id
SELECT v.* from vehicle v WHERE bol_id = ?;

--name: update-transfer-bol-by-move-id!
UPDATE transfer
SET bol_id = ?
WHERE vehicle_id = (SELECT id FROM vehicle WHERE move_id = ?);

--name: add-bol-status!
INSERT INTO bol_status (
  bol_id,
  bol_status_type_id,
  date_created
) VALUES (
  ?,
  (SELECT id FROM bol_status_type WHERE name = ?),
  ?
);
