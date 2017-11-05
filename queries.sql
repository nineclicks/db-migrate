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

--name: get-order-by-bol-id
SELECT
  o.*,
  b.date_created as bol_date_created
FROM "order" o
INNER JOIN vehicle v  on o.id = v.order_id
INNER JOIN transfer t on v.id = t.vehicle_id
INNER JOIN bol b      on b.id = t.bol_id
WHERE b.id = ?
LIMIT 1;

--name: get-vehicles-by-order-id
SELECT * FROM vehicle WHERE order_id = ?;

--name: get-location-by-id
SELECT * FROM location where id = ? limit 1;

--name: get-locations
SELECT * FROM location;

--name: add-invoice!
INSERT INTO invoice (
  base_cost,
  fuel_surcharge_percent,
  fuel_surcharge_amt,
  price_per_load,
  price_per_unit,
  additional_charge,
  additional_charge_desc,
  date_created
) VALUES (
  ?,?,?,?,?,?,?,?
) RETURNING id;

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
  cod,
  cop,
  move_type,
  eta,
  date_created,
  date_deactivated)
VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?);

--name: add-vehicle!
INSERT INTO "vehicle" (
  order_id,
  year,
  make,
  model,
  vin,
  classification,
  po_number,
  transfer_cid,
  curb_weight,
  doors,
  move_reason,
  important,
  promise_date,
  date_created,
  date_cancelled)
VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
RETURNING id;

--name: add-transfer!
INSERT INTO "transfer" (
  vehicle_id,
  pickup_location_id,
  dropoff_location_id
) VALUES (?,?,?);

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
  id,
  driver_id,
  shipment_id,
  date_created,
  pickup_location_id,
  dropoff_location_id
) VALUES (
  ?,
  (SELECT id FROM driver WHERE username = ?),
  ?,
  ?,
  (SELECT id FROM location WHERE name = ? AND street_address = ? AND city = ?),
  (SELECT id FROM location WHERE name = ? AND street_address = ? AND city = ?)
) RETURNING *;

--name: get-vehicles-by-bol-id
SELECT v.* from vehicle v WHERE bol_id = ?;

--name: update-transfer-bol-by-move-id!
UPDATE transfer
SET bol_id = ?
WHERE vehicle_id = (SELECT id FROM vehicle WHERE transfer_cid = ?);

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

--name: update-vehicle-invoice-id!
UPDATE vehicle
SET invoice_id = ?
WHERE id = ?;

--name: get-vehicles-with-bols
SELECT
  b.id as bol_id,
  v.id as vehicle_id
FROM vehicle v
INNER JOIN transfer t on t.vehicle_id = v.id
INNER JOIN bol b      on t.bol_id     = b.id;

--name: update-bol-seq!
SELECT setval('bol_id_seq', (SELECT MAX(id)+1 FROM bol));
