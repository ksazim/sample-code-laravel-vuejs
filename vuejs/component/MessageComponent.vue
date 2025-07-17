<script setup>
import ThumbnailRound from '@/components/thumbnail/ThumbnailRound.vue';
import { ref, computed, defineProps } from 'vue'
import { useCrudStore } from '@/store/crud'
import { useAuthStore } from '@/store/auth'
import { useChatStore } from '@/store/chat';
import axios from 'axios'

const props = defineProps({
    type: String,
    data: Object,
    chatId: String
})

const crudStore = useCrudStore()
const authStore = useAuthStore()
const chatStore = useChatStore()
const imageExtensions = ["jpg", "jpeg", "png", "gif", "bmp", "webp", "svg"];
const docExtensions = ["doc", "docx"];

const isPdf = computed(() => {
    return (props.data.file.split(".").pop().toLowerCase() == 'pdf') ? true : false
});

const isDoc = computed(() => {
  const extension = props.data.file.split(".").pop().toLowerCase(); 
  return (docExtensions.includes(extension) ? true : false); 
});

const isImage = computed(() => {
  const extension = props.data.file.split(".").pop().toLowerCase(); 
  return (imageExtensions.includes(extension) ? true : false); 
});

const isVideo = computed(() => {
    return (props.data.file.split(".").pop().toLowerCase() == 'mp4') ? true : false
});

function remove(id) {
    crudStore.destroy('delete-message/', id).then((response) => {
        console.log(response)
        if(response.status == 200) {
            chatStore.getMessages(props.chatId)
        } 
    }).catch((error) => {
        console.log(error)
    })
}
const baseServerUri = process.env.VUE_APP_BASE_SERVER_URL
const baseApi = process.env.VUE_APP_BASE_API_URL 

const showIcon = ref(false);

const isDownloading = ref(false);
const downloadProgress = ref(0);

async function download(file) {
  try { 
    isDownloading.value = true; // Start downloading
    downloadProgress.value = 0; // Reset progress

    // const name = encodeURIComponent(file.split("/").pop())
    const name = encodeURIComponent(file)
    axios.defaults.headers.common['Authorization'] = `Bearer ${authStore.token}`;
    const response = await axios.get(`${baseApi}dnld?filename=${name}`, {
        responseType: "blob", // Fetches the file as binary data
        headers: {
            "Content-Type": "application/json",
        },
    });

    console.log(response)
    
    const blob = response.data; // The binary file
    const url = window.URL.createObjectURL(blob);

    // Create an anchor element to trigger the download
    const link = document.createElement("a");
    link.href = url;

    // Extract the filename or use a default
    link.download = name.split('/').pop(); // Gets the last part of the path as the filename
    link.click(); // Trigger the download

    // Revoke the Blob URL to free up resources
    window.URL.revokeObjectURL(url);
  } catch (error) {
    console.error("Error downloading file:", error);
  } finally {
    isDownloading.value = false; // Stop downloading
    downloadProgress.value = 0; // Reset progress
  }
}

</script>

<template>
    <div v-if="type=='guest'" class="message-wrapper guest">
        <div class="message">
            <ThumbnailRound :path="data.user.photo" :height="'40px'" :width="'40px'" /> 
            <div class="message-body">
                <div class="content">
                    {{ (!data.content || data.content=='undefined') ? null : data.content }} 
                    <div v-if="data.file && isImage" class="image-container" @mouseover="showIcon = true" @mouseleave="showIcon = false">
                        <img @click="download(data.file)" class="image" v-if="data.file" :src="baseServerUri+data.file" alt="">
                        <font-awesome-icon 
                        :icon="['fa', 'download']" 
                        class="download-icon" 
                        :class="{ 'visible': showIcon }" />
                    </div>
                    <p v-if="downloadProgress > 0 && downloadProgress < 100">
                        Downloading... {{ downloadProgress }}%
                    </p>
                    <div v-if="data.file && isDoc" class="image-container" @mouseover="showIcon = true" @mouseleave="showIcon = false">
                        <font-awesome-icon @click="download(data.file)" class="file-icon" :icon="['far', 'file-word']" />
                        <font-awesome-icon 
                        :icon="['fa', 'download']" 
                        class="download-icon" 
                        :class="{ 'visible': showIcon }" />
                        <span>{{ data.file.split("/").pop() }}</span>
                        <p v-if="downloadProgress > 0 && downloadProgress < 100">
                            Downloading... {{ downloadProgress }}%
                        </p>
                    </div>
                    <div v-if="data.file && isPdf" class="image-container" @mouseover="showIcon = true" @mouseleave="showIcon = false">
                        <font-awesome-icon @click="download(data.file)" class="file-icon" :icon="['far', 'file-pdf']" />
                        <font-awesome-icon 
                        :icon="['fa', 'download']" 
                        class="download-icon" 
                        :class="{ 'visible': showIcon }" />
                        <span>{{ data.file.split("/").pop() }}</span>
                        <p v-if="downloadProgress > 0 && downloadProgress < 100">
                            Downloading... {{ downloadProgress }}%
                        </p>
                    </div>
                    <div v-if="data.file && isVideo" class="image-container" @mouseover="showIcon = true" @mouseleave="showIcon = false">
                        <font-awesome-icon @click="download(data.file)" class="file-icon" :icon="['fa', 'video']" />
                        <font-awesome-icon 
                        :icon="['fa', 'download']" 
                        class="download-icon" 
                        :class="{ 'visible': showIcon }" />
                        <span>{{ data.file.split("/").pop() }}</span>
                        <p v-if="downloadProgress > 0 && downloadProgress < 100">
                            Downloading... {{ downloadProgress }}%
                        </p>
                    </div>
                </div>
                <span class="time">({{ data.created_at }})</span>
            </div>
            <!-- <div class="action">
                <font-awesome-icon :icon="['fa', 'flag']" />
            </div>  -->
        </div>
    </div>
    <div v-if="type=='self'" class="message-wrapper self">
        <div class="message">
            <span>Me:  </span>
            <div class="message-body">
                <div class="content">
                    {{ (!data.content || data.content=='undefined') ? null : data.content }} 
                    <div v-if="data.file && isImage" class="image-container" @mouseover="showIcon = true" @mouseleave="showIcon = false">
                        <img @click="download(data.file)" class="image" v-if="data.file" :src="baseServerUri+data.file" alt="">
                        <font-awesome-icon 
                        :icon="['fa', 'download']" 
                        class="download-icon" 
                        :class="{ 'visible': showIcon }" />
                        <p v-if="downloadProgress > 0 && downloadProgress < 100">
                            Downloading... {{ downloadProgress }}%
                        </p>
                    </div>
                    <div v-if="data.file && isDoc" class="image-container" @mouseover="showIcon = true" @mouseleave="showIcon = false">
                        <font-awesome-icon @click="download(data.file)" class="file-icon" :icon="['far', 'file-word']" />
                        <font-awesome-icon 
                        :icon="['fa', 'download']" 
                        class="download-icon" 
                        :class="{ 'visible': showIcon }" />
                        <span>{{ data.file.split("/").pop() }}</span>
                        <p v-if="downloadProgress > 0 && downloadProgress < 100">
                            Downloading... {{ downloadProgress }}%
                        </p>
                    </div>
                    <div v-if="data.file && isPdf" class="image-container" @mouseover="showIcon = true" @mouseleave="showIcon = false">
                        <font-awesome-icon @click="download(data.file)" class="file-icon" :icon="['far', 'file-pdf']" />
                        <font-awesome-icon 
                        :icon="['fa', 'download']" 
                        class="download-icon" 
                        :class="{ 'visible': showIcon }" />
                        <span>{{ data.file.split("/").pop() }}</span>
                        <p v-if="downloadProgress > 0 && downloadProgress < 100">
                            Downloading... {{ downloadProgress }}%
                        </p>
                    </div>
                    <div v-if="data.file && isVideo" class="image-container" @mouseover="showIcon = true" @mouseleave="showIcon = false">
                        <font-awesome-icon @click="download(data.file)" class="file-icon" :icon="['fa', 'video']" />
                        <font-awesome-icon 
                        :icon="['fa', 'download']" 
                        class="download-icon" 
                        :class="{ 'visible': showIcon }" />
                        <span>{{ data.file.split("/").pop() }}</span>
                        <p v-if="downloadProgress > 0 && downloadProgress < 100">
                            Downloading... {{ downloadProgress }}%
                        </p>
                    </div>
                </div>
                <span class="time">({{ data.created_at }})</span>
            </div>
            <div class="action">
                <font-awesome-icon class="delete-msg" @click="remove(data.id)" :icon="['fa', 'remove']" />
            </div> 
        </div>
    </div>
</template>

<style scoped>
.message-wrapper {
    border-bottom: 1px solid #ececec;
    padding: 20px;
    box-sizing: border-box;
    text-decoration: none;
    color: #e0e0e0;
}

.message {
    width: 100%;
    display: grid;
    grid-template-columns: 5% 90% 5%;
    gap: 10px;
}

.guest {
    display: flex;
    justify-content: start;
    align-items: center;
    color: #202020;
}

.self {
    display: flex;
    justify-content: end;
    align-items: center;
    color: #13472d;
    font-weight: bold;
}

/* .message-wrapper:hover {
    background-color: #1d1d1d;
} */

.message > * {
    align-self: center;
}

.action {
    display: flex;
    align-items: center;
    justify-content: end;
    color: #ff7171;
}

.content {
    display: grid;
    gap: 15px;
}

.content img {
    border-radius: 5px;
}

.time {
    font-size: 14px;
}


.image-container {
  position: relative;
  display: grid;
  gap: 5px;
  width: 250px !important; /* Set a width for the container */
  overflow: hidden;
}

.image {
  width: 100%;
  height: 100%;
  object-fit: cover; /* Ensure the image fits nicely */
  display: block;
}

.download-icon {
  position: absolute;
  top: 50%;
  left: 10%;
  transform: translate(-50%, -50%);
  font-size: 2rem;
  color: white;
  background-color: rgba(51, 51, 51, 0.6); /* Add a semi-transparent background */
  padding: 0.5rem;
  border-radius: 50%;
  opacity: 0;
  transition: opacity 0.3s ease;
  pointer-events: none; /* Prevent the icon from blocking hover events */
}

.download-icon.visible {
  opacity: 1; /* Show the icon on hover */
  cursor: pointer;
}

.file-icon {
    font-size: 50px; 
    cursor: pointer;
}

.delete-msg {
    cursor: pointer;
}

.delete-msg:hover {
    color: #ad0000;
}
</style>
