#include <ESP8266WiFi.h>
#include <ESP8266HTTPClient.h>
#include <math.h>
// defines pins numbers
const int trigPin = 12;
const int echoPin = 14;
const int COUNTERMAX = 300;

// defines variables
long duration;
int distance;
int MasterCounter = COUNTERMAX;
String myid = "xxxx";
String key = "xxxx";
bool POWER_SAVE;
bool wifiON;

class DistanceObject {
  int maxMovement;
  int movement;  // counts movement, and counts down to post movement end
  public:
  int primaryDistance;   // this is the base distance of room
  int primaryCount;      // how many times the primary has been counted
  int secondaryDistance; // this is the movement distance, or the new possible base distance
  int secondaryCount;    // how many times the secondary has been counted, used to set new primary from secondary distance

  DistanceObject() {
    primaryDistance = 0;
    primaryCount = 0;
    secondaryDistance = 0;
    secondaryCount = 0;
    movement = 0;
    maxMovement = 8;  // used to count down to detect room empty
  }

  void ManageDistance(int inDist) {
    if (inDist > 1170 || inDist < 6) { return; }
    // set the base values if not set
    if(primaryDistance == 0) {
      primaryDistance = inDist;
      primaryCount = primaryCount + 1;
    } else if(secondaryDistance == 0) {
    }

    //Serial.println("inDist:" + String(inDist));
    //Serial.println("primaryDistance:" + String(primaryDistance) + " -- " + String(primaryCount));
    //Serial.println("secondaryDistance:" + String(secondaryDistance) + " -- " + String(secondaryCount));
   
    if(fabs(inDist - primaryDistance) < 12) {
      //Serial.println("Primary count ++ movement:" + String(movement));
      primaryCount = primaryCount + 1;
      if(primaryCount > 1000) { 
        primaryCount = 200; 
        secondaryCount = 0;
      }
      if(movement > 1) {
        movement = movement - 1;
        // last state was movement, count down
        Serial.println("Movement case countdown");
      } else if(movement == 1)
      {
        // SEND MOVEMENT ENDED CASE
        Serial.println("MOVEMENT CASE ENDED");  
        movement = 0;
        secondaryDistance = 0;
        secondaryCount = 0;
        SetState(0);
      }
    } else if(fabs(inDist - secondaryDistance) < 12) {
      //Serial.println("Secondary count ++");
      secondaryCount = secondaryCount + 1;
      if(movement > 0) {
        movement = maxMovement;  
      }
      if(secondaryCount > 100 || (primaryCount < secondaryCount && secondaryCount < 20)) {
        // new primary assignment
        primaryDistance = inDist;
        primaryCount = secondaryCount;
        secondaryCount = 0;
        secondaryDistance = 0;
        //Serial.println("New Primary being set from secondary");
      }
    } else {
      // new value possible, movement or setting new default
      if(primaryCount > 20) {
        // SEND MOVEMENT STARTED CASE
        Serial.println("MOVEMENT DETECTED");
        secondaryDistance = inDist;
        secondaryCount = 1;
        if(movement == 0) {
          // SEND THE NEW MOVEMENT EVENT
          //Serial.println("MOVEMENT STARTED, SEND UPDATE!!!!!!!!");
          SetState(1);
        }
        movement = maxMovement;  // change this for a countdown approximation
      } else {
        //Serial.println("New Baseline detected");
        secondaryDistance = inDist;
        secondaryCount = 1;
      }
    }
  }

  void SetState(int newstate) {
    if(WiFi.status()== WL_CONNECTED){
       HTTPClient http;
       //String temp = "http://svhack.yetilair.com/endpoints/?action=log&id=" + myid + "&state=" + String(newstate);
       String temp = "http://noqueue.yetilair.com/sys/?method=sensor&id=" + myid + "&key=" + key + "&request=setStatus&status=" + String(newstate);
       http.begin(temp);
       http.addHeader("Content-Type", "text/plain");
     
       int httpCode = http.POST("Status update from a Pooper");
       String payload = http.getString();

       Serial.println(String(newstate));
       Serial.println(httpCode);
       Serial.println(payload);
       http.end();
    }else{
       Serial.println("Error in WiFi connection");   
    }
  }

  void startDeepSleep(int sleepMinutes) {
    if(sleepMinutes > 0) {
      Serial.print("start deep sleep:");
      Serial.println(sleepMinutes);
      ESP.deepSleep(sleepMinutes * 60 * 1000000);
      //ESP.deepSleep(2000000);
    }
  }
  
  void ManageSleep() {
    if(WiFi.status()== WL_CONNECTED){
       HTTPClient http;
       String temp = "http://noqueue.yetilair.com/sys/?method=sensor&id=" + myid + "&key=" + key + "&request=getSleepTime";
       http.begin(temp);
       http.addHeader("Content-Type", "text/plain");
       int httpCode = http.POST("Get my sleep data");
       String sleepminutes = http.getString();
       Serial.println(sleepminutes);

       //ESP.restart();
       if(sleepminutes.length() > 0) {
        startDeepSleep(sleepminutes.toInt());
       }
       
       Serial.println("Sleep request return:");
       http.end();  //Close connection
    }else{
       Serial.println("Error in WiFi connection");   
    }
  }
};
DistanceObject Master;

void loop() {
  // Clears the trigPin
  digitalWrite(trigPin, LOW);
  delayMicroseconds(2);
  
  // Sets the trigPin on HIGH state for 10 micro seconds
  digitalWrite(trigPin, HIGH);
  delayMicroseconds(10);
  digitalWrite(trigPin, LOW);
  
  // Reads the echoPin, returns the sound wave travel time in microseconds
  duration = pulseIn(echoPin, HIGH);
  
  // Calculating the distance
  distance= duration*0.034/2;
  distance = distance*0.393;
  //Serial.println(String(distance));
  Master.ManageDistance(distance);
  if(MasterCounter <= 0) {
    MasterCounter = COUNTERMAX;
    Master.ManageSleep();
  } else {
    //Serial.println("MasterCounter:" + String(MasterCounter));
    MasterCounter = MasterCounter - 1;
  }
  delay(2000);  
}

void setup() {
  POWER_SAVE = true;
  pinMode(trigPin, OUTPUT); // Sets the trigPin as an Output
  pinMode(echoPin, INPUT); // Sets the echoPin as an Input
  Serial.begin(9600); // Starts the serial communication
  Serial.print("Started ");
  Master = DistanceObject();

  //pinMode(LED_BUILTIN, OUTPUT);

  // config static IP
  IPAddress ip(192, 168, 10, 52);
  IPAddress gateway(192, 168, 10, 1);
  Serial.print("Setting static ip to : ");
  Serial.println(ip);
  IPAddress subnet(255, 255, 255, 0);
  WiFi.config(ip, gateway, subnet);
  
  WiFi.begin("MYWIFINAME", "MYWIFIPASSWORD");
  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
    Serial.println("Waiting for connection");
  }
  //Serial.println(WiFi.macAddress());
}


