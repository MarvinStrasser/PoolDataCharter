import serial
import mariadb
import sys
from datetime import datetime

# Connect to MariaDB Platform
try:
    conn = mariadb.connect(
        user="admin",
        password="admin",
        host="localhost",
        port=3306,
        database="measurements"

    )
except mariadb.Error as e:
    print(f"Error connecting to MariaDB Platform: {e}")
    sys.exit(1)

# Get Cursor
cur = conn.cursor()

# Listen on USB/Serial port ACM0
ser = serial.Serial('/dev/ttyACM0', 9600, timeout=1)
ser.reset_input_buffer()
while True:
    if ser.in_waiting > 0:
        try:
            line = ser.readline().decode('utf-8').rstrip() # Read and decode data coming from Serial port ACM0
            ph, orp, temp = line.split(',')
            now = datetime.now()
            dt_string = now.strftime("%Y-%m-%d %H:%M:%S")
            print(dt_string + " " + ph + " " + orp + " " + temp)
            cur.execute("INSERT INTO data (time, ph, orp, temp) VALUES (?, ?, ?, ?)", (dt_string, ph, orp, temp))
            conn.commit()
#            cur.execute(query)
        except Exception as e:
            print("Error while decoding, reading or splitting data: ")
            print(e)
