from sense_emu import SenseHat
import time
import requests

s = SenseHat()
interval = 80
last_time = time.time()
threshold = 20

prev_x, prev_y, prev_z = 0, 0, 0
collision = False
cleared = False
setup = False
last_action = 0

with open('data.xml', 'w') as f:
    f.write("<?xml version='1.0' encoding='UTF-8'?>\n<records></records>") #to clear data.xml
print("Cleared data.xml at script start")

while True:
    curr_time = time.time()

    for event in s.stick.get_events():
        if event.action == 'pressed':
            if event.direction == 'middle':
                if collision:
                    print("Collision cleared")
                    collision = False
                    cleared = True
                    s.clear()
                elif not setup:
                    setup = True
                    last_action = curr_time
                    print("Entered setup mode. Threshold:", threshold)
                    s.show_message("SETUP", scroll_speed=0.05)
                else:
                    setup = False
                    print("Exited setup mode")
                    s.show_message("EXIT", scroll_speed=0.05)

            if setup:
                last_action = curr_time
                if event.direction == 'up':
                    threshold += 1
                    print("Threshold increased to:", threshold)
                    s.show_message(str(threshold), scroll_speed=0.05)
                elif event.direction == 'down':
                    threshold -= 1
                    print("Threshold decreased to:", threshold)
                    s.show_message(str(threshold), scroll_speed=0.05)

    if setup and (curr_time - last_action) > 10:
        setup = False
        print("Setup mode timeout")
        s.show_message("TIMEOUT", scroll_speed=0)

    if setup:
        continue

    humidity = s.get_humidity()
    temp = s.get_temperature()

    accel = s.get_accelerometer_raw()
    x = round(accel['x'], 3)
    y = round(accel['y'], 3)
    z = round(accel['z'], 3)

    dx = abs(x - prev_x)
    dy = abs(y - prev_y)
    dz = abs(z - prev_z)
    movement = dx + dy + dz

    prev_x, prev_y, prev_z = x, y, z

    print("Temp:", temp)
    print("Humidity:", humidity)
    print("Movement:", movement)

    if movement > 0.2 and not collision:
        before_collision = {
        'coll_state': 0,
        'light_level': humidity,
        'power_level': temp,
        'light_threshold': threshold,
        'timestamp': time.time()
    }
    try:
        response = requests.get("http://iotserver.com/canvasjs3.6/datafortuts/Assignment1.php", params=before_collision)
        print("Logged just before collision:", before_collision)
    except Exception as e:
        print("Error posting pre-collision:", e)

    collision = True
    print("Collision detected!")

    if (curr_time - last_time) > interval or collision:
        last_time = curr_time
        params = {
            'coll_state': int(collision),
            'light_level': humidity,
            'power_level': temp,
            'light_threshold': threshold,
            'timestamp' : time.time()
        }
        try:
            response = requests.get("http://iotserver.com/canvasjs3.6/datafortuts/Assignment1.php", params=params)
            print("Sent to server (GET):", params)
            print("Server response:", response.text)
        except Exception as e:
            print("Server error:", e)

    if cleared:
        params = {
            'coll_state': 0,
            'light_level': humidity,
            'power_level': temp,
            'light_threshold': threshold,
            'timestamp' : time.time()
        }
        try:
            response = requests.get("http://iotserver.com/canvasjs3.6/datafortuts/Assignment1.php", params=params)
            print("Sent after collision cleared (GET):", params)
            print("Server response:", response.text)
        except Exception as e:
            print("Error posting clear:", e)
        cleared = False

    if collision:
        if temp < 0:
            if (curr_time % 2) < 1:
                s.clear(0, 0, 255)  # Blue
            else:
                s.clear()

        if temp > 100:
            if (curr_time % 2) < 1:
                s.clear(255, 0, 0)  # Red
            else:
                s.clear()

        if humidity < threshold:
            if (curr_time % 2) < 1:
                s.clear(255, 255, 0)  # Yellow
            else:
                s.clear()

    
        if temp >= 0 and temp <= 100 and humidity >= threshold:
            if (curr_time % 2) < 1:
                s.clear(60, 179, 113)  #green
            else:
                s.clear()

        time.sleep(1)
        continue