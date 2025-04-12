<template>
  <v-app dark>
    <v-dialog v-model="dialog1" width="700">
      <v-card>
        <v-card-title class="pa-3">
          Connect Database
          <v-spacer></v-spacer>
          <v-badge :color="response.color" class="mt-3 mr-3"></v-badge>
        </v-card-title>

        <v-card-text class="pa-3">
          <v-text-field
            v-model="path"
            label="Path"
            color="teal"
            outlined
            dense
          ></v-text-field>
          <v-text-field
            v-model="database_name"
            label="Database Name"
            color="teal"
            outlined
            dense
          ></v-text-field>
          <div :class="`${response.color}--text`">{{ response.message }}</div>
        </v-card-text>

        <v-divider></v-divider>

        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn color="red darken-4" small dark @click="dialog1 = false">
            close
          </v-btn>
          <v-btn
            :loading="loading"
            color="blue-grey darken-4"
            small
            dark
            @click="connect"
          >
            Connect
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
    <v-dialog v-model="dialog2" width="700">
      <v-card>
        <v-card-title class="pa-3">
          Map Devices
          <v-spacer></v-spacer>
          <v-btn @click="add" small class="primary">
            <v-icon>mdi-plus</v-icon>
          </v-btn>
        </v-card-title>

        <v-card-text class="pa-3">
          <v-row dense v-for="(d, index) in devices" :key="index">
            <v-col md="5">
              <v-text-field
                :rules="requiredRule"
                v-model="devices[index].c_in"
                label="C/in"
                color="teal"
                outlined
                dense
              ></v-text-field>
            </v-col>
            <v-col md="5">
              <v-text-field
                v-model="devices[index].c_out"
                label="C/out"
                color="teal"
                outlined
                dense
              ></v-text-field>
            </v-col>
            <v-col md="2">
              <v-icon color="error" @click="deleteItem(index)"
                >mdi-delete</v-icon
              >
            </v-col>
          </v-row>
          <div :class="`${response.color}--text`">{{ response.message }}</div>
        </v-card-text>

        <v-divider></v-divider>

        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn color="red darken-4" small dark @click="dialog2 = false">
            close
          </v-btn>
          <v-btn
            :loading="loading"
            color="blue-grey darken-4"
            small
            dark
            @click="save"
          >
            Save
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
    <v-app-bar class="blue" fixed app dark dense>
      <v-container>
        <v-row>
          <v-col>
            <div>
              {{ title }}
            </div>
          </v-col>
          <v-col class="text-right">
            <!-- <v-btn small @click="dialog1 = true">
              Connect Database
              <v-icon right dark>mdi-connection</v-icon>
            </v-btn> -->
            <v-btn
              small
              @click="
                () => {
                  dialog2 = true;
                  response.message = '';
                }
              "
            >
              Map Devices
              <v-icon right dark>mdi-laptop</v-icon>
            </v-btn>
          </v-col>
        </v-row>
      </v-container>
    </v-app-bar>
    <v-main>
      <Nuxt />
    </v-main>
    <v-navigation-drawer v-model="rightDrawer" :right="right" temporary fixed>
      <v-list>
        <v-list-item @click.native="right = !right">
          <v-list-item-action>
            <v-icon light> mdi-repeat </v-icon>
          </v-list-item-action>
          <v-list-item-title>Switch drawer (click me)</v-list-item-title>
        </v-list-item>
      </v-list>
    </v-navigation-drawer>
    <v-footer :absolute="!fixed" app>
      <span>&copy; {{ new Date().getFullYear() }}</span>

      <v-spacer></v-spacer>
    </v-footer>
  </v-app>
</template>

<script>
export default {
  name: "DefaultLayout",
  data() {
    return {
      requiredRule: [(v) => !!v || "Field is required"],
      dialog1: false,
      dialog2: false,
      clipped: false,
      drawer: false,
      fixed: false,
      items: [
        {
          icon: "mdi-apps",
          title: "Welcome",
          to: "/",
        },
        {
          icon: "mdi-chart-bubble",
          title: "Inspire",
          to: "/inspire",
        },
      ],
      miniVariant: false,
      right: true,
      rightDrawer: false,
      loading: false,
      devices: [{ c_in: "", c_out: "" }],

      title: "Logger",
      upload: {
        name: "",
      },
      response: {
        status: false,
        color: "",
        message: "",
      },

      path: null,
      database_name: null,
    };
  },
  created() {
    this.check_connection();
    this.get_connection();
    this.get_devices();
  },
  methods: {
    get_devices() {
      this.$axios.get("/devices").then(({ data }) => {
        if (!data.length) return;
        this.devices = data;
      });
    },

    save() {
      this.$axios.post("/devices", this.devices).then(({ data }) => {
        if (data.status) {
          this.get_devices();

          this.response = {
            status: true,
            color: "primary",
            message: data.message,
          };
          return;
        }
        this.response = {
          status: data.status,
          color: "error",
          message: data.message,
        };
      });
    },
    add() {
      this.devices.push({ c_in: "", c_out: "" });
    },
    deleteItem(i) {
      this.devices.splice(i, 1);
    },
    connect() {
      let payload = {
        path: this.path,
        database_name: this.database_name,
      };
      this.$axios
        .post("/setExternalConnection", payload)
        .then(({ data, status }) => {
          if (status !== 200) {
            return;
          }

          this.path = data.path;
          this.database_name = data.database_name;

          this.check_connection();
        });
    },
    check_connection() {
      this.$axios
        .get("/check_external_database_connection")
        .then(({ data, status }) => {
          this.response = {
            status: true,
            color: "primary",
            message: data.message,
          };
        })
        .catch(({ response }) => {
          if (!response) {
            return false;
          }

          this.response = {
            status: false,
            color: "error",
            message: response.data.message,
          };
        });
    },
    get_connection() {
      this.$axios
        .get("/getExternalConnection")
        .then(({ data, status }) => {
          this.path = data.path;
          this.database_name = data.database_name;
        })
        .catch(({ response }) => {
          if (!response) {
            return false;
          }

          this.response = {
            status: false,
            color: "error",
            message: response.data.message,
          };
        });
    },
  },
};
</script>
