package id.familytree.family_tree_mobile

import io.flutter.embedding.android.FlutterActivity
import android.os.Bundle
import android.app.NotificationChannel
import android.app.NotificationManager
import android.os.Build

class MainActivity : FlutterActivity() {
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
            val channel = NotificationChannel(
                "family_updates",
                "Pembaruan keluarga",
                NotificationManager.IMPORTANCE_DEFAULT,
            ).apply {
                description = "Acara, aktivitas, dan pembaruan keluarga"
                lockscreenVisibility = android.app.Notification.VISIBILITY_PRIVATE
            }
            getSystemService(NotificationManager::class.java)
                .createNotificationChannel(channel)
        }
    }
}
